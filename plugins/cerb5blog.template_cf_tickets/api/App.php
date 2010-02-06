<?php

class Cerb5BlogCfTicketsEmailSignatureTemplate extends Extension_EmailSignatureTemplate {
  function __construct($manifest) {
    $this->DevblocksExtension($manifest,1);
  }

  function run($ticket_id, $signature) {
		if($ticket_id==0) {
			return;
		}
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket_id);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
				$signature = str_replace(
					array('#cf_ticket_' . $field->name . '#'),
					array($custom_fields[$ticket_id][$field->id]),
					$signature
				);
			}
		}
		return;
  }
  
  function render($list) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
					$cf_ticket['#cf_ticket_' . $field->name . '#'] = $field->name;
			}
		}
		$list['Custom Ticket Fields'] = $cf_ticket;
    return;
  }
};

class Cerb5BlogCfTicketsAutoReplyNew extends Extension_AutoReplyNew {
  function __construct($manifest) {
    $this->DevblocksExtension($manifest,1);
  }

  function run(CerberusTicket $ticket, $properties) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
				$properties = str_replace(
					array('#cf_ticket_' . $field->name . '#'),
					array($custom_fields[$ticket->id][$field->id]),
					$properties
				);
			}
		}
		return;
  }
  
	function render($list) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
					$cf_ticket['#cf_ticket_' . $field->name . '#'] = $field->name;
			}
		}
		$list['Custom Ticket Fields'] = $cf_ticket;
    return;
	}
};

class Cerb5BlogCfTicketsAutoReplyClose extends Extension_AutoReplyClose {
  function __construct($manifest) {
    $this->DevblocksExtension($manifest,1);
  }

  function run(CerberusTicket $ticket, $properties) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
				$properties['content'] = str_replace(
					array('#cf_ticket_' . $field->name . '#'),
					array($custom_fields[$ticket->id][$field->id]),
					$properties['content']
				);
			}
		}
		return;
	}
  
  function render($list) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
					$cf_ticket['#cf_ticket_' . $field->name . '#'] = $field->name;
			}
		}
		$list['Custom Ticket Fields'] = $cf_ticket;
    return;
  }
};

class Cerb5BlogCfTicketsEmailTemplate extends Extension_EmailTemplate {
  function __construct($manifest) {
    $this->DevblocksExtension($manifest,1);
  }

  function run($message_id, $template) {
    if(!empty($message_id)) {
      $message = DAO_Ticket::getMessage($message_id);
			$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
			$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $message->ticket_id);
		
			foreach ($fields as $field_id => $field) {
				if($field->group_id==0) {
					$template = str_replace(
						array('#cf_ticket_' . $field->name . '#'),
						array($custom_fields[$message->ticket_id][$field->id]),
						$template
					);
				}
			}
		}
		return;
  }
  
  function render($type, $list) {
		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		
		foreach ($fields as $field_id => $field) {
			if($field->group_id==0) {
					$cf_ticket['#cf_ticket_' . $field->name . '#'] = $field->name;
			}
		}
		$list['Custom Ticket Fields'] = $cf_ticket;
    return;
  }
};

