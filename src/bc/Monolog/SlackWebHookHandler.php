<?php


namespace bc\Monolog;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class SlackWebHookHandler extends AbstractProcessingHandler {

    /**
     * @var string
     */
    private $hookUrl;
    private $userName;
    private $iconEmoji = null;
    private $channel = null;

    private $colorSchema = [
        Logger::DEBUG     => '#7EAEF1',
        Logger::INFO      => '#70FF70',
        Logger::NOTICE    => '#999999',
        Logger::WARNING   => '#FFA500',
        Logger::ERROR     => '#FF6B68',
        Logger::CRITICAL  => '#BC3F3C',
        Logger::ALERT     => '#450505',
        Logger::EMERGENCY => '#450505'
    ];

    /**
     * @var Client
     */
    private $client;

    /**
     * SlackWebHookHandler constructor.
     *
     * @param array $options
     * @param bool|int $level
     * @param bool $bubble
     *
     */
    public function __construct(array $options, $level = Logger::DEBUG, $bubble = true) {
        parent::__construct($level, $bubble);

        if(!isset($options['webhookUrl'])) throw new \InvalidArgumentException();
        $this->hookUrl = $options['webhookUrl'];

        $this->userName = isset($options['userName']) ? $options['userName'] : 'Monolog';

        if(isset($options['iconEmoji'])) $this->iconEmoji = $options['iconEmoji'];
        if(isset($options['channel'])) $this->channel = $options['channel'];
        if(isset($options['colorSchema'])) $this->colorSchema = $options['colorSchema'];

        $this->client = new Client();
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record) {
        $message = [
            'username' => $this->userName
        ];

        if(!is_null($this->iconEmoji)) $message['icon_emoji'] = $this->iconEmoji;
        if(!is_null($this->channel)) $message['channel'] = $this->channel;

        $attachment = [
            "fallback"  => $record['formatted'],
            "color"     => $this->colorSchema[$record['level']],
            "title"     => "{$record['channel']}: {$record['level_name']}",
            "text"      => $record['message'],
            "mrkdwn_in" => ["text"]
        ];

        if(count($record['context']) > 0) {
            $fileds = [];
            foreach($record['context'] as $key => $value) {
                $fileds[] = [
                    "title" => $key,
                    "value" => $value,
                    "short" => true
                ];
            }
            $attachment['text'] .= "\n\n*Context*\n\n";
            $attachment['fields'] = $fileds;
        }

        $message['attachments'][] = $attachment;

        $json = json_encode($message);
        $body = \GuzzleHttp\Psr7\stream_for($json);

        $request = new Request("POST", $this->hookUrl);
        $request = $request->withHeader("Content-type", "application/json");
        $request = $request->withBody($body);

        $this->client->send($request, ['verify' => false]);
    }
}