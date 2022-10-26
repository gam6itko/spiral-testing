# Spiral Framework testing SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spiral/testing.svg?style=flat-square)](https://packagist.org/packages/spiral/testing)
[![Total Downloads](https://img.shields.io/packagist/dt/spiral/testing.svg?style=flat-square)](https://packagist.org/packages/spiral/testing)

## Requirements

Make sure that your server is configured with following PHP version and extensions:

- PHP 8.1+
- Spiral framework 3.0+

## Installation

You can install the package via composer:

```bash
composer require spiral/testing
```

## Spiral App testing

### TestApp configuration

#### Tests folders structure:

```
- tests
    - TestCase.php
    - Unit
      - MyFirstTestCase.php
      - ...
    - Feature
      - Controllers
        - HomeControllerTestCase.php
      ...
    - TestApp.php
```

Create test App class and implement `Spiral\Testing\TestableKernelInterface`

```php
namespace Tests\App;

class TestApp extends \App\App implements \Spiral\Testing\TestableKernelInterface
{
    use \Spiral\Testing\Traits\TestableKernel;
}
```

### TestCase configuration

Extend your TestCase class from `Spiral\Testing\TestCase` and implements a couple of required methods:

```php
namespace Tests;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function createAppInstance(): TestableKernelInterface
    {
        return \Spiral\Tests\App\TestApp::create(
            $this->defineDirectories($this->rootDirectory()),
            false
        );
    }
}
```

## Spiral package testing

There are some difference between App and package testing. One of them - tou don't have application and bootloaders.

TestCase from the package has custom TestApp implementation that will help you to test your packages without creating
extra classes.

The following example will show you how it is easy-peasy.

#### Tests folders structure:

```
tests
  - app
    - config
      - my-config.php
    - ...
  - src
    - TestCase.php
    - MyFirstTestCase.php
```

### TestCase configuration

```php
namespace MyPackage\Tests;

abstract class TestCase extends \Spiral\Testing\TestCase
{
    public function rootDirectory(): string
    {
        return __DIR__.'/../';
    }

    public function defineBootloaders(): array
    {
        return [
            \MyPackage\Bootloaders\PackageBootloader::class,
            // ...
        ];
    }
}
```

## Usage

### Application running

Application will be run automatically via `setUp` method in `Spiral\Testing\TestCase`. If you need to run application by
your self, you may disable automatic running.

```php
final class SomeTest extends BaseTest
{
    public const MAKE_APP_ON_STARTUP = false;

    public function testSomeFeature(): void
    {
        $this->initApp(env: [
            // ...
        ]);
    }
}
```

### Environment variables

You have two options to pass ENV variables to into your application instance:

1. By using `ENV` const.

```php
class KernelTest extends BaseTest
{
    public const ENV = [
        'FOO' => 'BAR'
    ];

    public function testSomeFeature(): void
    {
        //
    }
}
```

2. By running application by yourself

```php
final class SomeTest extends BaseTest
{
    public const MAKE_APP_ON_STARTUP = false;

    public function testSomeFeature(): void
    {
        $this->initApp(env: [
            // ...
        ]);
    }
}
```

### Booting callbacks

If you need to rebind some bound containers, you can do it via starting callbacks. You can create as more callbacks as
you want.

**Make sure that you create callbacks before application run**.

```php
abstract class TestCase extends \Spiral\Testing\TestCase
{
    protected function setUp(): void
    {
        // !!! Before parent::setUp() !!!

        // Before application init
        $this->beforeInit(static function(\Spiral\Core\Container $container) {

            $container->bind(\Spiral\Queue\QueueInterface::class, // ...);

        });

        // Before application booting
        $this->beforeBooting(static function(\Spiral\Core\Container $container) {

            $container->bind(\Spiral\Queue\QueueInterface::class, // ...);

        });

        parent::setUp();
    }
}
```

### Interaction with Http

```php
$response = $this->fakeHttp()
    ->withHeaders(['Accept' => 'application/json'])
    ->withHeader('CONTENT_TYPE', 'application/json')
    ->withActor(new UserActor())
    ->withServerVariables(['SERVER_ADDR' => '127.0.0.1'])
    ->withAuthorizationToken('token-hash', 'Bearer') // Header => Authorization: Bearer token-hash
    ->withCookie('csrf', '...')
    ->withSession([
        'cart' => [
            'items' => [...]
        ]
    ])
    ->withEnvironment([
        'QUEUE_CONNECTION' => 'sync'
    ])
    ->withoutMiddleware(MyMiddleware::class)
    ->get('/post/1')

$response->assertStatus(200);
```

#### Requests

```php
$http = $this->fakeHttp();
$http->withHeaders(['Accept' => 'application/json']);

$http->get(uri: '/', query: ['foo' => 'bar'])->assertOk();
$http->getJson(uri: '/')->assertOk();

$http->post(uri: '/', data: ['foo' => 'bar'], headers: ['Content-type' => '...'])->assertOk();
$http->postJson(uri: '/')->assertOk();

$http->put(uri: '/', cookies: ['token' => '...'])->assertOk();
$http->putJson(uri: '/')->assertOk();

$http->delete(uri: '/')->assertOk();
$http->deleteJson(uri: '/')->assertOk();
```

#### Request response

```php
/** @var \Spiral\Testing\Http\FakeHttp $http */
$response = $http->get(uri: '/', query: ['foo' => 'bar']);

// Check if header presents in response
$response->assertHasHeader('Content-type');

// Check if header missed in response
$response->assertHeaderMissing('Content-type');

// Get status code
$code = $response->getStatusCode();

// Check status code
$response->assertStatus(200);
$response->assertOk(); // code: 200
$response->assertCreated(); // code: 201
$response->assertAccepted(); // code:
$response->assertNoContent(); // code: 204
$response->assertNoContent(status: 204); // code: 204
$response->assertNotFound(); // code: 404
$response->assertForbidden(); // code: 403
$response->assertUnauthorized(); // code: 401
$response->assertUnprocessable(); // code: 422

// Check body
$response->assertBodySame('OK');
$response->assertBodyNotSame('OK');
$response->assertBodyContains('Hello world');

// Get body content
$body = (string) $response;

// Check cookie
$response->assertCookieExists('foo');
$response->assertCookieMissed('foo');
$response->assertCookieSame(key: 'foo', value: 'bar');

$cookies = $response->getCookies();

// Check if response is redirect to another page
$this->assertTrue($response->isRedirect());

// Get original response
$response = $response->getOriginalResponse();


```

#### Working with uploading files

```php
$http = $this->fakeHttp();

// Create a file with size - 100kb
$file = $http->getFileFactory()->createFile('foo.txt', 100);

// Create a file with specific content
$file = $http->getFileFactory()->createFileWithContent('foo.txt', 'Hello world');

// Create a fake image 640x480
$image = $http->getFileFactory()->createImage('fake.jpg', 640, 480);

$http->post(uri: '/', files: ['avatar' => $image, 'documents' => [$file]])->assertOk();
```

#### Working with storage

```php
// Will replace all buckets into with local adapters
$storage = $this->fakeStorage();

// Do something with storage
// $image = new UploadedFile(...);
// $storage->bucket('uploads')->write(
//    $image->getClientFilename(),
//    $image->getStream()
// );

$uploads = $storage->bucket('uploads');

$uploads->assertExists('image.jpg');
$uploads->assertCreated('image.jpg');

$public = $storage->bucket('public');
$public->assertNotExist('image.jpg');
$public->assertNotCreated('image.jpg');

// $public->delete('file.txt');
$public->assertDeleted('file.txt');
$uploads->assertNotDeleted('file.txt');
$public->assertNotExist('file.txt');

// $public->move('file.txt', 'folder/file.txt');
$public->assertMoved('file.txt', 'folder/file.txt');
$uploads->assertNotMoved('file.txt', 'folder/file.txt');

// $public->copy('file.txt', 'folder/file.txt');
$public->assertCopied('file.txt', 'folder/file.txt');
$uploads->assertNotCopied('file.txt', 'folder/file.txt');

// $public->setVisibility('file.txt', 'public');
$public->assertVisibilityChanged('file.txt');
$uploads->assertVisibilityNotChanged('file.txt');
```

### Interaction with Mailer

```php
protected function setUp(): void
{
    parent::setUp();
    $this->mailer = $this->fakeMailer();
}

protected function testRegisterUser(): void
{
    // run some code

    $this->mailer->assertSent(UserRegisteredMail::class, function (MessageInterface $message) {
        return $message->getTo() === 'user@site.com';
    })
}
```

#### assertSent

```php
$this->mailer->assertSent(UserRegisteredMail::class, function (MessageInterface $message) {
    return $message->getTo() === 'user@site.com';
})
```

#### assertNotSent

```php
$this->mailer->assertNotSent(UserRegisteredMail::class, function (MessageInterface $message) {
    return $message->getTo() === 'user@site.com';
})
```

#### assertSentTimes

```php
$this->mailer->assertSentTimes(UserRegisteredMail::class, 1);
```

#### assertNothingSent

```php
$this->mailer->assertNothingSent();
```

### Interaction with Events

```php
protected function setUp(): void
{
    parent::setUp();
    $this->eventDispatcher = $this->fakeEventDispatcher();
}
```

#### assertListening

Assert if an event has a listener attached to it.

```php
$this->eventDispatcher->assertListening(SomeEvent::class, SomeListener::class);
```

#### assertDispatched

Assert if an event was dispatched based on a truth-test callback.

```php
// Assert if an event dispatched one or more times
$this->eventDispatcher->assertDispatched(SomeEvent::class);


// Assert if an event dispatched one or more times based on a truth-test callback.
$this->eventDispatcher->assertDispatched(SomeEvent::class, static function(SomeEvent $event): bool {
    return $event->someParam === 100;
});
```

#### assertDispatchedTimes

Assert if an event was dispatched a number of times.

```php
$this->eventDispatcher->assertDispatchedTimes(SomeEvent::class, 5);
```

#### assertNotDispatched

Determine if an event was dispatched based on a truth-test callback.

```php
$this->eventDispatcher->assertNotDispatched(SomeEvent::class);

$this->eventDispatcher->assertNotDispatched(SomeEvent::class, static function(SomeEvent $event): bool {
    return $event->someParam === 100;
});
```

#### assertNothingDispatched

Assert that no events were dispatched.

```php
$this->eventDispatcher->assertNothingDispatched();
```

#### dispatched

Get all the events matching a truth-test callback.

```php
$this->eventDispatcher->dispatched(SomeEvent::class);

// or

$this->eventDispatcher->dispatched(SomeEvent::class, static function(SomeEvent $event): bool {
    return $event->someParam === 100;
});
```

#### hasDispatched

```php
$this->eventDispatcher->hasDispatched(SomeEvent::class);
```

### Interaction with Queue

```php
protected function setUp(): void
{
    parent::setUp();
    $this->connection = $this->fakeQueue();
    $this->queue = $this->connection->getConnection();
}

protected function testRegisterUser(): void
{
    // run some code

    $this->queue->assertPushed('mail.job', function (array $data) {
        return $data['handler'] instanceof \Spiral\SendIt\MailJob
            && $data['options']->getQueue() === 'mail'
            && $data['payload']['foo'] === 'bar';
    });

    $this->connection->getConnection('redis')->assertPushed('another.job', ...);
}
```

#### assertPushed

```php
$this->mailer->assertPushed('mail.job', function (array $data) {
    return $data['handler'] instanceof \Spiral\SendIt\MailJob
        && $data['options']->getQueue() === 'mail'
        && $data['payload']['foo'] === 'bar';
});
```

#### assertPushedOnQueue

```php
$this->mailer->assertPushedOnQueue('mail', 'mail.job', function (array $data) {
    return $data['handler'] instanceof \Spiral\SendIt\MailJob
        && $data['payload']['foo'] === 'bar';
});
```

#### assertPushedTimes

```php
$this->mailer->assertPushedTimes('mail.job', 2);
```

#### assertNotPushed

```php
$this->mailer->assertNotPushed('mail.job', function (array $data) {
    return $data['handler'] instanceof \Spiral\SendIt\MailJob
        && $data['options']->getQueue() === 'mail'
        && $data['payload']['foo'] === 'bar';
});
```

#### assertNothingPushed

```php
$this->mailer->assertNothingPushed();
```

### Interactions with container

#### assertBootloaderLoaded

```php
$this->assertBootloaderLoaded(\MyPackage\Bootloaders\PackageBootloader::class);
```

#### assertBootloaderMissed

```php
$this->assertBootloaderMissed(\Spiral\Framework\Bootloaders\Http\HttpBootloader::class);
```

#### assertContainerMissed

```php
$this->assertContainerMissed(\Spiral\Queue\QueueConnectionProviderInterface::class);
```

#### assertContainerInstantiable

Checking if container can create an object with autowiring

```php
$this->assertContainerInstantiable(\Spiral\Queue\QueueConnectionProviderInterface::class);
```

#### assertContainerBound

Checking if container has alias and bound with the same interface

```php
$this->assertContainerBound(\Spiral\Queue\QueueConnectionProviderInterface::class);
```

Checking if container has alias with specific class

```php
$this->assertContainerBound(
    \Spiral\Queue\QueueConnectionProviderInterface::class,
    \Spiral\Queue\QueueManager::class
);

// With additional parameters

$this->assertContainerBound(
    \Spiral\Queue\QueueConnectionProviderInterface::class,
    \Spiral\Queue\QueueManager::class,
    [
        'foo' => 'bar'
    ]
);

// With callback

$this->assertContainerBound(
    \Spiral\Queue\QueueConnectionProviderInterface::class,
    \Spiral\Queue\QueueManager::class,
    [
        'foo' => 'bar'
    ],
    function(\Spiral\Queue\QueueManager $manager) {
        $this->assertEquals(..., $manager->....)
    }
);
```

#### assertContainerBoundNotAsSingleton

```php
$this->assertContainerBoundNotAsSingleton(
    \Spiral\Queue\QueueConnectionProviderInterface::class,
    \Spiral\Queue\QueueManager::class
);
```

#### assertContainerBoundAsSingleton

```php
$this->assertContainerBoundAsSingleton(
    \Spiral\Queue\QueueConnectionProviderInterface::class,
    \Spiral\Queue\QueueManager::class
);
```

#### mockContainer

The method will bind alias with mock in the application container.

```php
function testQueue(): void
{
    $manager = $this->mockContainer(\Spiral\Queue\QueueConnectionProviderInterface::class);
    $manager->shouldReceive('getConnection')->once()->with('foo')->andReturn(
        \Mockery::mock(\Spiral\Queue\QueueInterface::class)
    );

    $queue = $this->getContainer()->get(\Spiral\Queue\QueueInterface::class);
}
```

### Interaction with dispatcher

#### assertDispatcherRegistered

```php
$this->assertDispatcherRegistered(HttpDispatcher::class);
```

#### assertDispatcherMissed

```php
$this->assertDispatcherMissed(HttpDispatcher::class);
```

#### serveDispatcher

Check if dispatcher registered in the application and run method serve inside scope with passed bindings.

```php
$this->serveDispatcher(HttpDispatcher::class, [
    \Spiral\Boot\EnvironmentInterface::class => new \Spiral\Boot\Environment([
        'foo' => 'bar'
    ]),

]);
```

#### assertDispatcherCanBeServed

```php
$this->assertDispatcherCanBeServed(HttpDispatcher::class);
```

#### assertDispatcherCannotBeServed

```php
$this->assertDispatcherCannotBeServed(HttpDispatcher::class);
```

#### getRegisteredDispatchers

```php
/** @var class-string[] $dispatchers */
$dispatchers = $this->getRegisteredDispatchers();
```

### Interaction with Console

#### assertConsoleCommandOutputContainsStrings

```php
$this->assertConsoleCommandOutputContainsStrings(
    'ping',
    ['site' => 'https://google.com'],
    ['Site found', 'Starting ping ...', 'Success!']
);
```

#### assertCommandRegistered

```php
$this->assertCommandRegistered('ping');
```

#### runCommand

```php
$output = $this->runCommand('ping', ['site' => 'https://google.com']);

foreach (['Site found', 'Starting ping ...', 'Success!'] as $string) {
    $this->assertStringContaisString($string, $output);
}
```

### Interaction with Views

#### assertViewSame

```php
$this->assertViewSame('foo:bar', [
    'foo' => 'bar',
], '<html>...</html>')
```

#### assertViewContains

```php
$this->assertViewContains('foo:bar', [
    'foo' => 'bar',
], ['<div>...</div>', '<a href="...">...</a>'])
```

#### assertViewContains

```php
$this->assertViewNotContains('foo:bar', [
    'foo' => 'bar',
], ['<div class="hidden">...</div>'])
```

#### assertViewContains with specific locale

```php
$this->withLocale('fr')->assertViewSame('foo:bar', [
    'foo' => 'bar',
], '<div>...</div>')
```

### Interaction with Config

#### assertConfigMatches

```php
$this->assertConfigMatches('http', [
    'basePath'   => '/',
    'headers'    => [
        'Content-Type' => 'text/html; charset=UTF-8',
    ],
    'middleware' => [],
])
```

#### assertConfigHasFragments

```php
$config = [
    'basePath'   => '/',
    'headers'    => [
        'Content-Type' => 'text/html; charset=UTF-8',
    ],
    'middleware' => [],
]
```

```php
$this->assertConfigHasFragments('http', [
    'basePath' => '/'
])
```

#### getConfig

```php
/** @var array $config */
$config = $this->getConfig('http');
```

### Interactions with file system

#### assertDirectoryAliasDefined

```php
$this->assertDirectoryAliasDefined('runtime');
```

#### assertDirectoryAliasMatches

```php
$this->assertDirectoryAliasMatches('runtime', __DIR__.'src/runtime');
```

#### cleanupDirectories

```php
$this->cleanupDirectories(
    __DIR__.'src/runtime/cache',
    __DIR__.'src/runtime/tmp'
);
```

#### cleanupDirectoriesByAliases

```php
$this->cleanupDirectoriesByAliases(
    'runtime', 'app', '...'
);
```

#### cleanUpRuntimeDirectory

```php
$this->cleanUpRuntimeDirectory();
```

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [butschster](https://github.com/spiral-packages)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
