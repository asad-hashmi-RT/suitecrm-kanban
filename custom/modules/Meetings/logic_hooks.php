<?php
// Do not store anything in this file that is not part of the array or the hook version.  This file will	
// be automatically rebuilt in the future. 
 $hook_version = 1; 
$hook_array = Array(); 
// position, file, function 
$hook_array['after_save'] = Array(); 
$hook_array['after_save'][] = Array(77, 'updateMeetingGeocodeInfo', 'modules/Meetings/MeetingsJjwg_MapsLogicHook.php','MeetingsJjwg_MapsLogicHook', 'updateMeetingGeocodeInfo'); 
$hook_array['after_save'][] = Array(1, 'Meeting Calendar Sync After Save', 'modules/Meetings/MeetingCalendarSyncLogicHook.php','MeetingCalendarSyncLogicHook', 'afterSave'); 
$hook_array['after_delete'] = Array(); 
$hook_array['after_delete'][] = Array(1, 'Meeting Calendar Sync After Delete', 'modules/Meetings/MeetingCalendarSyncLogicHook.php','MeetingCalendarSyncLogicHook', 'afterDelete'); 



?>