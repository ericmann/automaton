<?php
namespace EAMann\Automaton\Slack;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    protected static $defaultName = 'slack:status';

    protected function configure()
    {
        $this
            ->setDescription('Set your Slack status.')
            ->setHelp('Set your Slack status.')
            ->addArgument('status', InputArgument::REQUIRED, 'What is your status message?')
            ->addArgument('emoji', InputArgument::OPTIONAL, 'What emoji should we display?', ':hammer_and_wrench:');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $presence = 'here';
        $status = $input->getArgument('status');
        $emoji = $input->getArgument('emoji' );
        $expires = 0;

        switch($status) {
            case 'wfh':
                $status = 'Working from home ...';
                $emoji = ':wfh:';
                break;
            case 'meeting':
                $status = 'In a meeting ...';
                $emoji = ':meeting:';
                $expires = time() + 60;
                break;
            case 'lunch':
                $status = 'AFK - eating something';
                $emoji = ':lunch:';
                $expires = time() + 60;
                break;
            case 'offline':
                $presence = 'out';
                $status = 'AFK - 503.925.6266 if it\'s an emergency';
                $emoji = ':offline:';
                break;
        }

        $client = new Client(['base_uri' => 'https://slack.com/api/']);

        $response = $client->request('POST', 'users.profile.set', [
            'headers' => [
                'User-Agent'    => 'displacetech/automaton',
                'Content-Type'  => 'application/json',
                'Authorization' => sprintf('Bearer %s', SLACK_TOKEN),
            ],
            'json' => [
                "profile" => [
                    "status_text"       => $status,
                    "status_emoji"      => $emoji,
                    "status_expiration" => $expires
                ]
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            if ($presence === 'out') {
                echo 'Going dark ...' . PHP_EOL;
                $response = $client->request('POST', 'users.setPresence', [
                    'headers' => [
                        'User-Agent' => 'displacetech/automaton',
                    ],
                    'form_params' => [
                        "presence" => "away",
                        "token"    => SLACK_TOKEN
                    ]
                ]);
            } else {
                $response = $client->request('POST', 'users.setPresence', [
                    'headers' => [
                        'User-Agent' => 'displacetech/automaton',
                    ],
                    'form_params' => [
                        "presence" => "auto",
                        "token"    => SLACK_TOKEN
                    ]
                ]);

                $response = $client->request('POST', 'users.setActive', [
                    'headers' => [
                        'User-Agent' => 'displacetech/automaton',
                    ],
                    'form_params' => [
                        "token" => SLACK_TOKEN
                    ]
                ]);
            }

            if ($response->getStatusCode() === 200) {
                return 0;
            }

        }

        echo $response->getReasonPhrase();
        return 1;
    }
}
