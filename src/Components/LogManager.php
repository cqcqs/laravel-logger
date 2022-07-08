<?php

namespace Cqcqs\Logger\Components;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogManager extends \Illuminate\Log\LogManager
{
    protected $agentLogHandler;
    protected $agentFormatter;
    protected $collectionChannels = [];
    protected $collectionEnable = false;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->init();
    }

    protected function init()
    {
        $this->collectionEnable = config('logger.enable');
        if (!$this->collectionEnable) {
            return;
        }
        $this->collectionChannels = config('logger.channels');
        $path = config('logger.path');
        $pos = strrpos($path, '.');
        if ($pos > 0) {
            $path = substr($path, 0, $pos) . '-' . date("Ymd") . substr($path, $pos);
        } else {
            $path .= '-' . date("Ymd") . '.log';
        }
        $fp = fopen($path, 'a');
        $this->agentLogHandler = new StreamHandler($fp);
        /*$formatter = new JsonFormatter();
        $formatter->includeStacktraces();
        $this->agentLogHandler->setFormatter($formatter);*/
        $this->agentFormatter = new JsonFormatter();
        $this->agentFormatter->includeStacktraces();
        $this->agentLogHandler->setFormatter($this->agentFormatter);
    }

    protected function isCollectionChannel($name, $excludeStackChannel): bool
    {
        if (!$this->collectionEnable) {
            return false;
        }
        if ($excludeStackChannel && $name == 'stack') {
            return false;
        }
        if ($this->collectionChannels && !in_array($name, $this->collectionChannels)) {
            return false;
        }
        return true;
    }

    protected function appendHandler($name, $logger)
    {
        if ($this->isCollectionChannel($name, true) && $logger instanceof Logger) {
            $logger->pushHandler($this->agentLogHandler);
        }
    }

    protected function resolve($name, $config=null): LoggerInterface
    {
        $reflectionClass = new \ReflectionClass(\Illuminate\Log\LogManager::class);
        $reflectionMethod = $reflectionClass->getMethod('resolve');
        if($reflectionMethod->getNumberOfParameters() == 2) {
            $logger = parent::resolve($name, $config);
        }else{
            $logger = parent::resolve($name);
        }
        $this->appendHandler($name, $logger);
        return $logger;
    }

    /**
     * @param string $name
     * @param \Illuminate\Log\Logger $logger
     * @return \Illuminate\Log\Logger
     */
    protected function tap($name, \Illuminate\Log\Logger $logger)
    {
        $taps = $this->configurationFor($name)['tap'] ?? [] ;
        if(!$taps){
            return $logger;
        }
        $logger = parent::tap($name,$logger);
        foreach ($logger->getHandlers() as $handler) {
            if ($handler === $this->agentLogHandler && $handler->getFormatter() != $this->agentFormatter) {
                $handler->setFormatter($this->agentFormatter);
            }
        }
        return $logger;
    }

    protected function createEmergencyLogger()
    {
        $logger = parent::createEmergencyLogger();
        if ($logger instanceof \Illuminate\Log\Logger) {
            $monoLogger = $logger->getLogger();
            $this->appendHandler('emergency', $monoLogger);
        }
        return $logger;
    }

    protected function configurationFor($name): array
    {
        $config = parent::configurationFor($name);
        if (is_array($config) && $this->isCollectionChannel($name, false)) {
            if (!isset($config['name'])) {
                $config['name'] = $name . '.' . config('app.env');
            }
        }
        return $config;
    }

}
