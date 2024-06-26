<?php

namespace App\Event\Subscriber;

use App\Event\ErrorEvent;
use App\Manager\LogManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ErrorEventSubscriber
 *
 * Event subscriber for handling error events.
 *
 * @package App\EventSubscriber
 */
class ErrorEventSubscriber implements EventSubscriberInterface
{
    private LogManager $logManager;

    public function __construct(LogManager $logManager)
    {
        $this->logManager = $logManager;
    }

    /**
     * Returns an array of subscribed events that this object should listen to.
     *
     * @return array<string> An array containing event names and corresponding methods to be called when events are dispatched.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ErrorEvent::NAME => 'onErrorEvent',
        ];
    }

    /**
     * Method called when an error event is dispatched.
     *
     * @param ErrorEvent $event The object representing the error event.
     *
     * @return void
     */
    public function onErrorEvent(ErrorEvent $event): void
    {
        // get error values
        $errorName = $event->getErrorName();
        $errorMessage = $event->getErrorMessage();

        // log error
        $this->logManager->log($errorName, $errorMessage);
    }
}
