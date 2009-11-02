<?php
/**
 * Calendar Event Notifications
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Event Notifications
 *
 * @package     Calendar
 */
 class Calendar_Controller_EventNotifications
 {
     const NOTIFICATION_LEVEL_NONE                      =  0;
     const NOTIFICATION_LEVEL_INVITE_CANCLE             = 10;
     const NOTIFICATION_LEVEL_EVENT_RESCHEDULE          = 20;
     const NOTIFICATION_LEVEL_EVENT_UPDATE              = 30;
     const NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE    = 40;
     
    /**
     * @var Calendar_Controller_EventNotifications
     */
    private static $_instance = NULL;
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Calendar_Controller_EventNotifications
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_EventNotifications();
        }
        
        return self::$_instance;
    }
    
    /**
     * constructor
     * 
     */
    private function __construct()
    {
        
    }
    
    /**
     * get updates of human interest
     * 
     * @param  Calendar_Model_Event $_event
     * @param  Calendar_Model_Event $_oldEvent
     * @return array
     */
    protected function _getUpdates($_event, $_oldEvent)
    {
        // check event details
        $diff = $_event->diff($_oldEvent);
        
        $updates = array_intersect_key($diff, array_flip(array(
            'dtstart', 'dtend', 'transp', 'class_id', 'description', 'geo', 'location',
            'organizer', 'priority', 'status_id', 'summary', 'url', /*'tags', 'notes',*/
            'rrule', 'is_all_day_event', 'originator_tz'
        )));
        
        // check attendee updates
        $attendeeMigration = $_oldEvent->attendee->getMigration($_event->attendee->getArrayOfIds());
        foreach ($attendeeMigration['toUpdateIds'] as $key => $attenderId) {
            $currAttender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
            $oldAttender  = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
            if ($currAttender->status != $oldAttender->status) {
                $attendeeMigration['toUpdateIds'][$key] = $currAttender;
            } else {
                unset($attendeeMigration['toUpdateIds'][$key]);
            }
        }
        foreach ($attendeeMigration['toCreateIds'] as $key => $attenderId) {
            $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
            $attendeeMigration['toCreateIds'][$key] = $attender;
        }
        foreach ($attendeeMigration['toDeleteIds'] as $key => $attenderId) {
            $attender = $_oldEvent->attendee[$_event->attendee->getIndexById($attenderId)];
            $attendeeMigration['toDeleteIds'][$key] = $attender;
        }
        
        $attendeeUpdates = array();
        foreach(array('toCreateIds', 'toDeleteIds', 'toUpdateIds') as $action) {
            if (! empty($attendeeMigration[$action])) {
                $attendeeUpdates[substr($action, 0, -3)] = array_values($attendeeMigration[$action]);
            }
        }
        
        if (! empty($attendeeUpdates)) {
            $updates['attendee'] = $attendeeUpdates;
        }
        
        return $updates;
    }
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @return void
     */
    public function sendNotifications($_event, $_updater, $_action, $_oldEvent=NULL)
    {
        // lets resolve attendee once as batch to fill cache
        $attendee = clone $_event->attendee;
        Calendar_Model_Attender::resolveAttendee($attendee);
        
        switch ($_action) {
            case 'alarm':
            case 'created':
            case 'deleted':
                foreach($_event->attendee as $attender) {
                    $this->sendNotificationToAttender($attender, $_event, $_updater, $_action);
                }
                break;
            case 'changed':
                $attendeeMigration = $_oldEvent->attendee->getMigration($_event->attendee->getArrayOfIds());
                
                foreach ($attendeeMigration['toCreateIds'] as $attenderId) {
                    $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_event, $_updater, 'created');
                }
                
                foreach ($attendeeMigration['toDeleteIds'] as $attenderId) {
                    $attender = $_oldEvent->attendee[$_oldEvent->attendee->getIndexById($attenderId)];
                    $this->sendNotificationToAttender($attender, $_oldEvent, $_updater, 'deleted');
                }
                
                // NOTE: toUpdateIds are all attendee to be notified
                if (! empty($attendeeMigration['toUpdateIds'])) {
                    $updates = $this->_getUpdates($_event, $_oldEvent);
                    
                    if (empty($updates)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " empty update, nothing to notify about");
                        return;
                    }
                    
                    // compute change type
                    if (count(array_intersect(array('dtstart', 'dtend'), array_keys($updates))) > 0) {
                        $updateLevel = self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE;
                    } else if (count(array_diff(array_keys($updates), array('attendee'))) > 0) {
                        $updateLevel = self::NOTIFICATION_LEVEL_EVENT_UPDATE;
                    } else {
                        $updateLevel = self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE;
                    }
                    
                    // send notifications
                    foreach ($attendeeMigration['toUpdateIds'] as $attenderId) {
                        $attender = $_event->attendee[$_event->attendee->getIndexById($attenderId)];
                        $this->sendNotificationToAttender($attender, $_event, $_updater, 'changed', $updates, $updateLevel);
                    }
                }
                
                break;
                
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
                
        }
    }
    
    /**
     * send notification to a single attender
     * 
     * @param Calendar_Model_Attender    $_attender
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param array                      $_updates
     * @return void
     */
    public function sendNotificationToAttender($_attender, $_event, $_updater, $_action, $_updates=NULL, $_updateLevel=NULL)
    {
        if (! in_array($_attender->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
            // don't send notifications to non persons
            return;
        }
        
        // find organizer account
        if ($_event->organizer) {
            $organizerContact = Addressbook_Controller_Contact::getInstance()->get($_event->organizer);
            $organizer = Tinebase_User::getInstance()->getFullUserById($organizerContact->account_id);
        } else {
            // use creator as organizer
            $organizer = Tinebase_User::getInstance()->getFullUserById($_event->created_by);
        }
        
        // get prefered language, timezone and notification level
        $prefUser = $_attender->getUserAccountId();
        if (! $prefUser) {
            $prefUser = $organizer;
        }
        $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $prefUser));
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $prefUser);
        $translate = Tinebase_Translation::getTranslation('Calendar', $locale);

        // get date strings
        $startDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtstart, $timezone, $locale);
        $endDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_event->dtend, $timezone, $locale);
        
        switch ($_action) {
            case 'alarm':
                $messageSubject = sprintf($translate->_('Alarm for event "%1$s" at %2$s'), $_event->summary, $startDateString);
                $messageBody = $translate->_('Here is your requested alarm for to following event:') . "\n\n";
                break;
            case 'created':
                $messageSubject = sprintf($translate->_('Event invitation "%1$s" at %2$s'), $_event->summary, $startDateString);
                $messageBody = $translate->_('You have been invited to the following event:') . "\n\n";
                break;
            case 'deleted':
                $messageSubject = sprintf($translate->_('Event "%1$s" at %s has been canceled' ), $_event->summary, $startDateString);
                $messageBody = $translate->_('The following event has been canceled:') . "\n\n";
                break;
            case 'changed':
                $messageBody = "\n";
                
                switch ($_updateLevel) {
                    case self::NOTIFICATION_LEVEL_EVENT_RESCHEDULE:
                        $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been rescheduled' ), $_event->summary, $startDateString);
                        $messageBody .= $translate->_('From') . ': ' . 
                            (array_key_exists('dtstart', $_updates) ? Tinebase_Translation::dateToStringInTzAndLocaleFormat($_updates['dtstart'], $timezone, $locale) : $startDateString) . " - " .
                            (array_key_exists('dtend', $_updates) ? Tinebase_Translation::dateToStringInTzAndLocaleFormat($_updates['dtend'], $timezone, $locale) : $endDateString) . "\n";
                        $messageBody .= $translate->_('To') . ': ' . $startDateString . ' - ' . $endDateString . "\n\n";
                        break;
                        
                    case self::NOTIFICATION_LEVEL_EVENT_UPDATE:
                        $messageSubject = sprintf($translate->_('Event "%1$s" at %2$s has been updated' ), $_event->summary, $startDateString);
                        break;
                        
                    case self::NOTIFICATION_LEVEL_ATTENDEE_STATUS_UPDATE:
                        if(! empty($_updates['attendee']) && ! empty($_updates['attendee']['toUpdate']) && count($_updates['attendee']['toUpdate']) == 1) {
                            // single attendee status update
                            $attender = $_updates['attendee']['toUpdate'][0];
                            $attenderName = $attender->getName();
                            
                            switch ($attender->status) {
                                case Calendar_Model_Attender::STATUS_ACCEPTED:
                                    $messageSubject = sprintf($translate->_('%1$s accepted event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_DECLINED:
                                    $messageSubject = sprintf($translate->_('%1$s declined event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_TENTATIVE:
                                    $messageSubject = sprintf($translate->_('Tentative response from %1$s for event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                                    
                                case Calendar_Model_Attender::STATUS_NEEDSACTION:
                                    $messageSubject = sprintf($translate->_('No response from %1$s for event "%2$s" at %3$s' ), $attender->getName(), $_event->summary, $startDateString);
                                    break;
                            }
                        } else {
                            $messageSubject = sprintf($translate->_('Attendee changes for event "%1$s" at %2$s' ), $_event->summary, $startDateString);
                        }
                        break;
                }
                
                // add updates
                $messageBody .= $translate->_('Changes:') . "\n";
                foreach($_updates as $field => $update) {
                    
                }              
                $messageBody .= $translate->_('Event details:') . "\n";

                break;
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " unknown action '$_action'");
                break;
        }
        
        // add values to text
        $messageBody .= $_event->summary . "\n\n" 
            . $translate->_('Start')        . ': ' . $startDateString   . "\n" 
            . $translate->_('End')          . ': ' . $endDateString     . "\n"
            //. $translate->_('Organizer')    . ': ' . $_event->organizer   . "\n" 
            . $translate->_('Location')     . ': ' . $_event->location    . "\n"
            . $translate->_('Description')  . ': ' . $_event->description . "\n\n"
            
            . $translate->plural('Attender', 'Attendee', count($_event->attendee)). ":\n";
        
        foreach ($_event->attendee as $attender) {
            $status = $translate->translate($attender->getStatusString());
            
            $messageBody .= "{$attender->getName()} ($status) \n";
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " receiver: '{$_attender->getEmail()}'");
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " subject: '$messageSubject'");
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " body: $messageBody");
        
        // NOTE: this is a contact as we only support users and groupmembers
        $contact = $_attender->getResolvedUser();
        Tinebase_Notification::getInstance()->send($organizer, array($contact), $messageSubject, $messageBody);
    }
 }