<?php
namespace Viperet\ObserverDump\Plugin;

class EventManager extends \Magento\Framework\Event\Manager
{
    private $events;
    private $filename;
    /**
     * @param \Magento\Framework\Filesystem\DirectoryList
     */
    private $dir;

    /**
     * @param \Psr\Log\LoggerInterface
     */
    private $log;

    public function __construct(
        \Magento\Framework\Event\InvokerInterface $invoker,
        \Magento\Framework\Event\ConfigInterface $eventConfig,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Psr\Log\LoggerInterface $log
    )
    {
        $this->dir = $dir;
        $this->log = $log;
        $this->loadEvents();
        parent::__construct($invoker, $eventConfig);
    }

    public function dispatch($eventName, array $data = [])
    {
        $eventData = [];
        foreach($data as $key=>$value) {
            $eventData[$key] = $this->getType($value);
        }
        if (isset($this->events[$eventName])) {
            $this->events[$eventName]['data'] = $eventData;
        } else {
            $this->events[$eventName] = [
                'description' => '',
                'data' => $eventData,
            ];
        }
        if ($eventName == 'session_abstract_clear_messages') $this->saveEvents();
//        $this->log->info('Event '.$eventName);
        parent::dispatch($eventName, $data);
    }

    private function getType($value)
    {
        if(is_object($value)) {
            return get_class($value);
        } else {
            return gettype($value);
        }
    }

    private function loadEvents()
    {
        $this->filename = $this->dir->getPath('var').'/events.json';
        if (file_exists($this->filename)) {
            $this->events = json_decode(file_get_contents($this->filename), true);
        } else {
            $this->events = [];
        }
    }

    private function saveEvents()
    {
        ksort($this->events);
        file_put_contents($this->filename, json_encode($this->events, JSON_PRETTY_PRINT));
//        $this->log->info('Saved events to '.$this->filename);
    }
}