<?php
/**
 * @license MIT
 * Copyright 2022 Dustin Wilson, et al.
 * See LICENSE and AUTHORS files for details
 */

declare(strict_types=1);
namespace MensBeam\Catcher\Test;
use MensBeam\Catcher\{
    Error,
    Handler,
    ThrowableController,
    AWSSNSHandler
};
use Aws\Sns\SnsClient,
    Psr\Log\LoggerInterface,
    Phake;


/** @covers \MensBeam\Catcher\AWSSNSHandler */
class TestAWSSNSHandler extends \PHPUnit\Framework\TestCase {
    protected ?SnsClient $client = null;
    protected ?AWSSNSHandler $handler = null;


    public function setUp(): void {
        parent::setUp();

        $this->client = Phake::mock(SnsClient::class);
        Phake::when($this->client)->publish()->thenReturn(true);

        $this->handler = new AWSSNSHandler($this->client, 'ook', [
            'outputBacktrace' => true,
            'silent' => true
        ]);
    }

    public function testGettingSettingProps() {
        $c = $this->handler->getClient();
        $this->assertTrue($c instanceof SnsClient);
        $c2 = Phake::mock(SnsClient::class);
        Phake::when($c2)->publish()->thenReturn(true);
        $this->handler->setClient($c2);
        $this->assertNotEquals($c, $this->handler->getClient());
        $this->assertTrue($this->handler->getClient() instanceof SnsClient);

        $this->assertSame('ook', $this->handler->getTopicARN());
        $this->handler->setTopicARN('eek');
        $this->assertSame('eek', $this->handler->getTopicARN());
    }

    /** @dataProvider provideInvocationTests */
    public function testInvocation(\Throwable $throwable, bool $silent, bool $log, ?string $logMethodName, ?array $ignore, int $line): void {
        $this->handler->setOption('outputToStderr', false);

        if (!$silent) {
            $this->handler->setOption('silent', false);
        }
        if ($log) {
            $l = Phake::mock(LoggerInterface::class);
            $this->handler->setOption('logger', $l);
        }
        if ($ignore !== null) {
            $this->handler->setOption('ignore', $ignore);
        }

        $o = $this->handler->handle(new ThrowableController($throwable));
        $c = $o['class'] ?? null;

        $h = $this->handler;
        $h();
        $u = $h->getLastOutputThrowable();

        if ($ignore === null) {
            $this->assertNotNull($u);
            $this->assertEquals($c, $u['class']);
            $this->assertEquals(__FILE__, $u['file']);
            $this->assertEquals($line, $u['line']);

            if (!$silent) {
                Phake::verify($this->client, Phake::times(1))->publish;
            }
        } else {
            $this->assertNull($u);
        }

        if ($log) {
            Phake::verify($l, Phake::times((int)(count($ignore ?? []) === 0)))->$logMethodName;
        }
    }


    public static function provideHandlingTests(): iterable {
        $options = [
            [ new \Exception('Ook!'), Handler::BUBBLES | Handler::EXIT, [ 'forceExit' => true ] ],
            [ new \Error('Ook!'), Handler::BUBBLES ],
            [ new Error('Ook!', \E_ERROR, '/dev/null', 42, new \Error('Eek!')), Handler::BUBBLES | Handler::NOW, [ 'forceOutputNow' => true ] ],
            [ new \Exception('Ook!'), Handler::BUBBLES, [ 'logger' => Phake::mock(LoggerInterface::class), 'logWhenSilent' => false ] ],
            [ new \Error('Ook!'), Handler::BUBBLES | Handler::LOG, [ 'forceOutputNow' => true, 'logger' => Phake::mock(LoggerInterface::class) ] ]
        ];

        foreach ($options as $o) {
            $o[1] |= Handler::NOW;
            yield $o;
        }
    }

    public static function provideInvocationTests(): iterable {
        $options = [
            [ new \Exception('Ook!'), false, true, 'critical', null ],
            [ new \Exception('Ook!'), false, true, 'critical', [ \Exception::class ] ],
            [ new \Error('Ook!'), true, false, null, null ],
            [ new \Error('Ook!'), true, false, null, [ \Error::class ] ],
            [ new Error('Ook!', \E_ERROR, __FILE__, __LINE__), false, true, 'error', null ],
            [ new Error('Ook!', \E_ERROR, __FILE__, __LINE__), false, true, 'error', [ \E_ERROR ] ],
            [ new \Exception(message: 'Ook!', previous: new \Error(message: 'Eek!', previous: new \ParseError('Ack!'))), true, true, 'critical', null ],
            [ new \Exception(message: 'Ook!', previous: new \Error(message: 'Eek!', previous: new \ParseError('Ack!'))), true, true, 'critical', [ \Exception::class ] ]
        ];

        $l = count($options);
        foreach ($options as $k => $o) {
            yield [ ...$o, __LINE__ - 4 - $l + $k ];
        }
    }
}