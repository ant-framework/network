<?php
namespace Ant\Network\Shadowsocks;

use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use React\Dns\Resolver\Factory as DnsFactory;

/**
 * todo 检查必须参数
 * required
 *  server_addr
 *  server_port
 *  timeout
 *  password
 *  method
 *
 * Class Application
 * @package Ant\Network\Shadowsocks
 */
class Application
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    protected $basePath;

    protected $configPath;

    protected $config = [];

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    public function getConfig()
    {
        if ($this->config) {
            return $this->config;
        }

        if (!$this->configPath) {
            $this->configPath = realpath($this->basePath) . '/config.json';
        }

        if (!file_exists($this->configPath)) {
            throw new \RuntimeException("{$this->configPath} not exists");
        }

        $config = json_decode(file_get_contents($this->configPath), true);

        if (false === $config) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }

        return $this->config = $config;
    }

    public function checkSapiEnv()
    {
        // 只能在cli模式下运行
        if (php_sapi_name() != "cli") {
            exit("only run in command line mode \n");
        }
    }

    public function parseCommand()
    {
        global $argv;

//        array_shift($argv);
//
//        for ($i = 0; $i < count($argv); ) {
//            $command = $argv[$i];
//        }
    }

    public function start()
    {
        // todo worker manager
        $dns = $this->getDnsResolver();

        foreach ($this->config['servers'] as $options) {
            new Server($this->loop, $dns, $options);
        }

        $this->loop->addPeriodicTimer(5, [$this, 'checkMemory']);

        $this->loop->run();
    }

    protected function initLoop()
    {
        $this->loop = LoopFactory::create();
    }

    protected function getDnsResolver()
    {
        $factory = new DnsFactory();

        $config = $this->getConfig();

        return $factory->create($config['dns_server'], $this->loop);
    }

    public function checkMemory()
    {
        $memory = memory_get_usage() / 1024;
        $formatted = number_format($memory, 3).'K';
        echo "Current memory usage: {$formatted}\n";
    }
}