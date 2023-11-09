<?php
/**
 * @license MIT
 * Copyright 2022 Dustin Wilson, et al.
 * See LICENSE and AUTHORS files for details
 */

declare(strict_types=1);
namespace MensBeam\Catcher;
use Aws\Sns\SnsClient;


class AWSSNSHandler extends JSONHandler {
    protected SnsClient $client;
    protected string $topicARN;




    public function __construct(SnsClient $client, string $topicARN, array $options = []) {
        $this->client = $client;
        $this->topicARN = $topicARN;
        parent::__construct($options);
    }




    public function getClient(): SnsClient {
        return $this->client;
    }

    public function getTopicARN(): string {
        return $this->topicARN;
    }

    public function setClient(SnsClient $client): void {
        $this->client = $client;
    }

    public function setTopicARN(string $topicARN): void {
        $this->topicARN = $topicARN;
    }


    protected function handleCallback(array $output): array {
        return $output;
    }

    protected function invokeCallback(): void {
        foreach ($this->outputBuffer as $o) {
            if (($o['code'] & self::OUTPUT) === 0) {
                if ($o['code'] & self::LOG) {
                    $this->serializeOutputThrowable($o);
                }

                continue;
            }

            $this->client->publish([
                'Message' => $this->serializeOutputThrowable($o),
                'TopicArn' => $this->topicARN
            ]);
        }
    }
}