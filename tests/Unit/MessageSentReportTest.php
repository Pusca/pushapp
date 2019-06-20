<?php
/**
 * @author Igor Timoshenkov [it@campoint.net]
 * @started: 2018-12-03 11:31
 */

namespace Minishlink\WebPush\Tests\Unit;

use GuzzleHttp\Psr7\Request;
use \Minishlink\WebPush\MessageSentReport;
use \GuzzleHttp\Psr7\Response;
use Minishlink\WebPush\Tests\TestCase;

/**
 * @covers \Minishlink\WebPush\MessageSentReport
 */
final class MessageSentReportTest extends TestCase
{

    /**
     * @param MessageSentReport $report
     * @param bool              $expected
     * @dataProvider generateReportsWithExpiration
     */
    public function testIsSubscriptionExpired(MessageSentReport $report, bool $expected): void
    {
        $this->assertEquals($expected, $report->isSubscriptionExpired());
    }

    /**
     * @return array
     */
    public function generateReportsWithExpiration(): array
    {
        $request = new Request('POST', 'https://example.com');
        return [
            [new MessageSentReport($request, new Response(404)), true],
            [new MessageSentReport($request, new Response(410)), true],
            [new MessageSentReport($request, new Response(500)), false],
            [new MessageSentReport($request, new Response(200)), false]
        ];
    }

    /**
     * @param MessageSentReport $report
     * @param string            $expected
     * @dataProvider generateReportsWithEndpoints
     */
    public function testGetEndpoint(MessageSentReport $report, string $expected): void
    {
        $this->assertEquals($expected, $report->getEndpoint());
    }

    /**
     * @return array
     */
    public function generateReportsWithEndpoints(): array
    {
        return [
            [new MessageSentReport(new Request('POST', 'https://www.example.com'), new Response(200)), 'https://www.example.com'],
            [new MessageSentReport(new Request('POST', 'https://m.example.com'), new Response(200)), 'https://m.example.com'],
            [new MessageSentReport(new Request('POST', 'https://test.net'), new Response(200)), 'https://test.net'],
        ];
    }

    /**
     * @param MessageSentReport $report
     * @param string            $json
     * @dataProvider generateReportsWithJson
     */
    public function testJsonSerialize(MessageSentReport $report, string $json): void
    {
        $this->assertJsonStringEqualsJsonString($json, json_encode($report));
    }

    public function generateReportsWithJson(): array
    {
        $request1Body = json_encode(['title' => 'test', 'body' => 'blah', 'data' => []]);
        $request1 = new Request('POST', 'https://www.example.com', [], $request1Body);
        $response1 = new Response(200, [], $request1Body);

        $request2Body = 'Faield to do somthing';
        $request2 = new Request('POST', 'https://www.example.com', [], $request2Body);
        $response2 = new Response(410, [], 'Faield to do somthing', '1.1', 'Gone');

        return [
            [
                new MessageSentReport($request1, $response1),
                json_encode([
                    'success'  => true,
                    'expired'  => false,
                    'reason'   => 'OK',
                    'endpoint' => (string) $request1->getUri(),
                    'payload'  => $request1Body,
                ])
            ],
            [
                new MessageSentReport($request2, $response2),
                json_encode([
                    'success'  => false,
                    'expired'  => true,
                    'reason'   => 'Gone',
                    'endpoint' => (string) $request2->getUri(),
                    'payload'  => $request2Body,
                ])
            ]
        ];
    }

    /**
     * @param MessageSentReport $report
     * @param bool              $expected
     * @dataProvider generateReportsWithSuccess
     */
    public function testIsSuccess(MessageSentReport $report, bool $expected): void
    {
        $this->assertEquals($expected, $report->isSuccess());
    }

    /**
     * @return array
     */
    public function generateReportsWithSuccess(): array
    {
        $request = new Request('POST', 'https://example.com');
        return [
            [new MessageSentReport($request, new Response(200)), true],
            [new MessageSentReport($request, new Response(200)), true],
            [new MessageSentReport($request, new Response(404)), false],
        ];
    }
}
