<?php

declare(strict_types=1);

namespace Nexus\Tests\Web;

use Nexus\Application;
use Nexus\Assets\AssetManager;
use Nexus\Auth\AuthenticatableInterface;
use Nexus\Auth\AuthManager;
use Nexus\Auth\UserProviderInterface;
use Nexus\Bootstrap;
use Nexus\Configuration\Configuration;
use Nexus\Contracts\ModuleInterface;
use Nexus\Http\HttpKernel;
use Nexus\Http\Request;
use Nexus\Routing\Router;
use Nexus\Security\CsrfTokenManager;
use Nexus\Session\ArraySession;
use Nexus\Validation\FormValidationException;
use Nexus\Validation\FormValidator;
use Nexus\Validation\Validator;
use Nexus\View\View;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

use function Nexus\view;

final class WebRuntimeTest extends TestCase
{
    /** @var list<string> */
    private array $directories = [];

    #[After]
    public function cleanUp(): void
    {
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }

                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($directory);
        }

        $this->directories = [];
    }

    public function testRequestParsesWebInputAndHtmlPreference(): void
    {
        $request = new Request(
            'POST',
            '/profile',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'text/html,application/xhtml+xml',
            ],
            ['page' => '2'],
            'name=Rafael&email=rafael%40example.com',
        );

        self::assertSame('Rafael', $request->input('name'));
        self::assertSame('2', $request->input('page'));
        self::assertTrue($request->acceptsHtml());
    }

    public function testFlashDataLivesForTheNextRequestOnly(): void
    {
        $session = new ArraySession();
        $session->flash('success', 'Saved');
        $session->ageFlashData();

        self::assertSame('Saved', $session->get('success'));

        $session->ageFlashData();
        self::assertFalse($session->has('success'));
    }

    public function testCsrfTokensAreSessionBackedAndRotatable(): void
    {
        $session = new ArraySession();
        $csrf = new CsrfTokenManager($session);
        $first = $csrf->token();

        self::assertTrue($csrf->verify($first));
        self::assertStringContainsString($first, $csrf->field());

        $second = $csrf->rotate();
        self::assertNotSame($first, $second);
        self::assertFalse($csrf->verify($first));
        self::assertTrue($csrf->verify($second));
    }

    public function testFormValidationFlashesErrorsAndSafeOldInput(): void
    {
        $session = new ArraySession();
        $forms = new FormValidator(new Validator(), $session);
        $request = new Request(
            'POST',
            '/signup',
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Referer' => '/signup'],
            [],
            'email=invalid&_token=secret&password=dont-keep-me',
        );

        try {
            $forms->validate($request, ['email' => 'required|email']);
            self::fail('Form validation should have failed.');
        } catch (FormValidationException $exception) {
            self::assertSame('/signup', $exception->redirectTo());
            self::assertArrayHasKey('email', $exception->errors());
        }

        $old = $session->get('_old_input');
        self::assertIsArray($old);
        self::assertSame('invalid', $old['email'] ?? null);
        self::assertArrayNotHasKey('_token', $old);
        self::assertArrayNotHasKey('password', $old);
        self::assertIsArray($session->get('_errors'));
    }

    public function testSessionAuthenticationUsesAnApplicationProvider(): void
    {
        $session = new ArraySession();
        $auth = new AuthManager($session, new FakeUserProvider());

        self::assertTrue($auth->attempt(['email' => 'rafael@example.com', 'password' => 'secret']));
        self::assertTrue($auth->check());
        self::assertTrue($auth->hasRole('admin'));

        $auth->logout();
        self::assertTrue($auth->guest());
    }

    public function testAssetManagerReadsAViteManifest(): void
    {
        $basePath = $this->temporaryDirectory();
        $manifestDirectory = $basePath . '/public/build/.vite';
        mkdir($manifestDirectory, 0777, true);
        file_put_contents($manifestDirectory . '/manifest.json', json_encode([
            'resources/frontend/app.js' => ['file' => 'assets/app-123.js'],
        ], JSON_THROW_ON_ERROR));

        $assets = new AssetManager($basePath);

        self::assertSame('/build/assets/app-123.js', $assets->url('resources/frontend/app.js'));
    }

    public function testViewHelperIsNormalizedIntoAnHtmlResponse(): void
    {
        $basePath = $this->temporaryDirectory();
        mkdir($basePath . '/resources/views', 0777, true);
        file_put_contents($basePath . '/resources/views/home.php', '<h1><?= $title ?></h1>');

        $application = Bootstrap::create(
            basePath: $basePath,
            configuration: new Configuration(['frontend' => ['renderer' => 'php']]),
        );
        $router = new Router();
        $router->get('/home', static fn (Request $request, array $parameters) => view('home', ['title' => 'Nexus']));

        $response = (new HttpKernel($router, $application->container()))->handle(
            new Request('GET', '/home', ['Accept' => 'text/html']),
        );

        self::assertSame(200, $response->status());
        self::assertSame('text/html; charset=utf-8', $response->headers()['content-type'] ?? null);
        self::assertSame('<h1>Nexus</h1>', $response->body());
    }

    public function testModulesAutomaticallyExposeTheirViewsByNamespace(): void
    {
        $basePath = $this->temporaryDirectory();
        $moduleViews = $basePath . '/modules/Catalog/Views';
        mkdir($basePath . '/resources/views', 0777, true);
        mkdir($moduleViews, 0777, true);
        file_put_contents($moduleViews . '/card.php', '<article><?= $name ?></article>');

        $application = Bootstrap::create(
            basePath: $basePath,
            configuration: new Configuration(['frontend' => ['renderer' => 'php']]),
        );
        $application->modules()->add(new CatalogTestModule());
        $application->boot();

        $views = $application->container()->get(View::class);
        self::assertInstanceOf(View::class, $views);
        self::assertSame('<article>Car</article>', $views->render('catalog::card', ['name' => 'Car']));

        $application->shutdown();
    }

    public function testHtmlErrorsUseTheWebRenderingPath(): void
    {
        $basePath = $this->temporaryDirectory();
        mkdir($basePath . '/resources/views', 0777, true);
        $application = Bootstrap::create(
            basePath: $basePath,
            configuration: new Configuration(['frontend' => ['renderer' => 'php']]),
        );

        $response = (new HttpKernel(new Router(), $application->container()))->handle(
            new Request('GET', '/missing', ['Accept' => 'text/html']),
        );

        self::assertSame(404, $response->status());
        self::assertSame('text/html; charset=utf-8', $response->headers()['content-type'] ?? null);
        self::assertStringContainsString('404 Not Found', $response->body());
    }

    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/nexus-web-' . bin2hex(random_bytes(6));
        mkdir($directory, 0777, true);
        $this->directories[] = $directory;

        return $directory;
    }
}

final readonly class FakeUser implements AuthenticatableInterface
{
    public function authIdentifier(): int|string
    {
        return 7;
    }

    public function roles(): array
    {
        return ['admin'];
    }
}

final class FakeUserProvider implements UserProviderInterface
{
    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        return (string) $identifier === '7' ? new FakeUser() : null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        return ($credentials['email'] ?? null) === 'rafael@example.com' ? new FakeUser() : null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        return ($credentials['password'] ?? null) === 'secret';
    }
}

final class CatalogTestModule implements ModuleInterface
{
    public function name(): string
    {
        return 'catalog';
    }

    public function register(Application $application): void
    {
    }

    public function boot(Application $application): void
    {
    }

    public function shutdown(Application $application): void
    {
    }
}
