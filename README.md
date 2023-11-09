[a]: https://code.mensbeam.com/MensBeam/Catcher
[b]: https://packagist.org/packages/aws/aws-sdk-php
[c]: https://github.com/symfony/yaml
[d]: https://www.php.net/manual/en/function.pcntl-fork.php
[e]: https://www.php.net/manual/en/function.print-r.php
[f]: https://github.com/symfony/var-exporter
[g]: https://github.com/php-fig/log

# AWSSNSHandler #

_AWSSNSHandler_ is a Throwable handler for use in [_Catcher_][a], a Throwable and error handling library for PHP. It sends throwables and errors to Amazon SNS topics. Right now _AWSSNSHandler_ only supports sending to email topics. SMS messages aren't supported at this time.


## Requirements ##

* PHP >= 8.1
* [mensbeam/catcher][a] ^2.1.2
* [aws/aws-sdk-php][b] ^3.283


## Installation ##

```shell
composer require mensbeam/catcher-awssnshandler
```


## Usage ##

For most use cases this library requires no configuration and little effort to integrate into non-complex environments:

```php
use MensBeam\Catcher,
    MensBeam\Catcher\AWSSNSHandler,
    Aws\Sns\SnsClient;

$client = new SnsClient([
    'version' => 'latest',
    'region' => 'us-west-2',
    'credentials' => [
        'key' => 'AKIAFIMBZAFZZQL42RMH',
        'secret' => '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'
    ]
]);
$catcher = new Catcher(new AWSSNSHandler($client, 'arn:aws:sns:us-west-2:701867229025:ook_eek'));
```

That's it. It will automatically register Catcher as an exception, error, and shutdown handler and use `AWSSNSHandler` as its sole handler. Like other _Catcher_ handlers, _AWSSNSHandler_ can be configured with a logger. When logging it behaves identically to _JSONHandler_. See the [_Catcher_][a] documentation for more info on how to configure a logger.

## Documentation ##

### MensBeam\Catcher\AWSSNSHandler ###

```php
namespace MensBeam\Catcher;
use Aws\Sns\SnsClient;


class AWSSNSHandler extends JSONHandler {
    protected SnsClient $client;
    protected string $topicARN;


    public function __construct(SnsClient $client, string $topicARN, array $options = []);

    public function getClient(): SnsClient;
    public function setClient(SnsClient $client): void;
    public function getTopicARN(): string;
    public function setTopicARN(string $topicARN): void;
}
```

#### MensBeam\Catcher\AWSSNSHandler::getClient ####

Returns the `Aws\Sns\SnsClient` the handler uses

#### MensBeam\Catcher\AWSSNSHandler::getTopicARN ####

Returns the AWS SNS topic ARN the handler sends messages to

#### MensBeam\Catcher\AWSSNSHandler::setClient ####

Replaces the `Aws\Sns\SnsClient` the handler uses with one specified

#### MensBeam\Catcher\AWSSNSHandler::setTopicARN ####

Replaces the AWS SNS topic ARN the handler sends messages to with one specified