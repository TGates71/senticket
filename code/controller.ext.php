<?php
// SenTicket for Sentora CP
// Rebuilt By       : TGates
// Original Author  : Diablo925

class module_controller extends ctrl_module
{
		static $ok;
		static $update;
		
    /**
     * The 'worker' methods.
     */

	static function doread()
    {
		global $zdbh, $controller;
        runtime_csfr::Protect();
		$currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		// start remove notice
		$sql = "SELECT * FROM x_accounts WHERE ac_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
		
		while ($row = $sql->fetch())
		{
			$old_notice = $row["ac_notice_tx"];
		}
		
		$new_notice = str_replace('You have a new support ticket reply.<br>', '', $old_notice);
		$new_notice = str_replace('You have a new support ticket.<br>', '', $new_notice);

		$sql = $zdbh->prepare("
			UPDATE x_accounts
			SET ac_notice_tx = :notice
			WHERE ac_id_pk = :uid");
		$sql->bindParam(':notice', $new_notice);
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
		// end remove notice
		if (isset($formvars['inRead']))
		{
			header("location: ./?module=" . $controller->GetCurrentModule() . '&show=read&ticket='. $formvars['innumber']. '');
			exit;
		}
		return true;
	}
	
	static function ExectuteSendTicket($domain, $subject, $msg)
	{
		global $zdbh, $controller;;
		$length = 4;
		$ticketnumber = fs_director::GenerateRandomPassword($length, 4);
		$date = date("Y-m-d");
		$ticketid = "$date.$ticketnumber"; 
		$Ticketstatus = "Open";
		$currentuser = ctrl_users::GetUserDetail();
		
		$sql = "SELECT * FROM x_accounts WHERE ac_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
        $sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
        while ($row = $sql->fetch())
		{
			$reseller = $row["ac_reseller_fk"];
			$old_notice = $row["ac_notice_tx"];
		}
		
		$sql = "SELECT * FROM x_profiles WHERE ud_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
        $sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
        while ($row = $sql->fetch())
		{
			$username = $row["ud_fullname_vc"];
		}
		
		$date = date("Y-m-d - H:i:s");
		$msg = "$date -- $username: $msg";
		
		$sql = $zdbh->prepare("INSERT INTO x_ticket (st_acc, st_number, st_domain, st_subject, st_meassge, st_status, st_groupid) VALUES (:uid, :number, :domain, :subject, :msg, :ticketstatus, :group)");
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->bindParam(':number', $ticketid);
		$sql->bindParam(':domain', $domain);
		$sql->bindParam(':subject', $subject);
		$sql->bindParam(':msg', $msg);
		$sql->bindParam(':ticketstatus', $Ticketstatus);
		$sql->bindParam(':group', $reseller);
        $sql->execute();
		
		// start admin notice update
		$notice = 'You have a new support ticket.<br>';
        $sql = $zdbh->prepare("
            UPDATE x_accounts
            SET ac_notice_tx = :notice
            WHERE ac_id_pk = :rid");
        $sql->bindParam(':notice', $notice);
        $sql->bindParam(':rid', $reseller);
        $sql->execute();
		// end admin notice update

		// start remove notice
		$new_notice = str_replace('You have a new support ticket reply.<br>', '', $old_notice);
		$new_notice = str_replace('You have a new support ticket.<br>', '', $new_notice);

        $sql = $zdbh->prepare("
            UPDATE x_accounts
            SET ac_notice_tx = :notice
            WHERE ac_id_pk = :uid");
        $sql->bindParam(':notice', $new_notice);
        $sql->bindParam(':uid', $currentuser['userid']);
        $sql->execute();
		// end remove notice
		
        self::$ok = true;
		return true;
	}
	static function ExectuteTicketUpdate($msg, $ticketid)
	{
		global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail();

		$sql_old = "SELECT * FROM x_ticket WHERE st_number = :number AND st_acc = :uid";
		$sql_old = $zdbh->prepare($sql_old);
		$sql_old->bindParam(':uid', $currentuser['userid']);
		$sql_old->bindParam(':number', $ticketid);
		$sql_old->execute();
		while ($row_old = $sql_old->fetch())
		{
			$oldmsg = $row_old["st_meassge"];
		}
		
		$sql = "SELECT * FROM x_accounts WHERE ac_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
        $sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
        while ($row = $sql->fetch())
		{
			$reseller = $row["ac_reseller_fk"];
			$old_notice = $row["ac_notice_tx"];
		}
		
		$sql = "SELECT * FROM x_profiles WHERE ud_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
        $sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
        while ($row = $sql->fetch())
		{
			$username = $row["ud_fullname_vc"];
		}
		
		$date = date("Y-m-d - H:i:s");
		$msg = "$oldmsg
		--------------------------------
		$date -- $username: $msg";
		
		$sql = $zdbh->prepare("UPDATE x_ticket SET st_meassge = :msg, st_status = :status WHERE st_number = :number AND st_acc = :uid");
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->bindParam(':number', $ticketid);
		$Ticketstatus = "Re-Opened";
		$sql->bindParam(':status', $Ticketstatus);
		$sql->bindParam(':msg', $msg);
        $sql->execute();

		// start admin notice update
		$notice = 'You have a new support ticket reply.<br>';
        $sql = $zdbh->prepare("
            UPDATE x_accounts
            SET ac_notice_tx = :notice
            WHERE ac_id_pk = :rid");
        $sql->bindParam(':notice', $notice);
        $sql->bindParam(':rid', $reseller);
        $sql->execute();
		// end admin notice update

		// start remove notice
		$new_notice = str_replace('You have a new support ticket reply.<br>', '', $old_notice);
		$new_notice = str_replace('You have a new support ticket.<br>', '', $new_notice);

        $sql = $zdbh->prepare("
            UPDATE x_accounts
            SET ac_notice_tx = :notice
            WHERE ac_id_pk = :uid");
        $sql->bindParam(':notice', $new_notice);
        $sql->bindParam(':uid', $currentuser['userid']);
        $sql->execute();
		// end remove notice
		
		self::$update = true;
		return true;
	}
	
	static function doselect()
    {
        global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		
            if (isset($formvars['inMyTicket']))
			{
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=MyTicket');
                exit;
            }
			if (isset($formvars['inNewTicket']))
			{
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=NewTicket');
                exit;
            }
        return true;
    }
	
	static function getisMyTicket()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "MyTicket");
    }
	
	static function getisread()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "read");
    }
	
	static function getisNewTicket()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "NewTicket");
    }

	static function ListSelectTicket($uid)
	{
		global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$urlvars = $controller->GetAllControllerRequests('URL');
		$ticket = $urlvars['ticket'];
		$sql = "SELECT * FROM x_ticket WHERE st_acc = :uid AND st_number = :number";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
		$numrows->bindParam(':number', $ticket);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0)
		{
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
			$sql->bindParam(':number', $ticket);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch())
			{
				$msg = nl2br($row['st_meassge']);
                array_push($res, array(
					'Ticket_number' => $row['st_number'],
					'Ticket_domain' => $row['st_domain'],
					'Ticket_subject' => $row['st_subject'],
					'Ticket_msg' => $msg
				));
            }
            return $res;
        }
		else
		{
            return false;
        }
		
	}
	
   	static function ListDomain($uid)
    {
        global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail();
        $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk = :uid AND vh_deleted_ts IS NULL";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0)
		{
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch())
			{
                array_push($res, array('dname' => $row['vh_name_vc']));
            }
            return $res;
        }
		else
		{
            return false;
        }
    }

	static function ListTicket($uid)
    {
		global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$sql = "SELECT * FROM x_ticket WHERE st_acc = :uid";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0)
		{
			$sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch())
			{
                array_push($res, array(
					'ticketid' => $row['st_id'],
					'ticketnumber' => $row['st_number'],
					'ticketdomain' => $row['st_domain'],
					'ticketsubject' => $row['st_subject'],
					'ticketstatus' => $row['st_status']
				));
            }
            return $res;
        }
		else
		{
            return false;
        }
	} 
	
    /**
     * End 'worker' methods.
     */

    /**
     * Webinterface sudo methods.
     */

	static function getTicket()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListSelectTicket($currentuser['userid']);
    }
	
    static function getDomainList()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListDomain($currentuser['userid']);
    }

	static function getTicketList()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListTicket($currentuser['userid']);
    } 

	static function doSendTicket()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        if (self::ExectuteSendTicket($formvars['inDomain'], $formvars['inSubject'], $formvars['inMessage']));
	}
	
	static function doUpdateTicket()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        if (self::ExectuteTicketUpdate($formvars['inMessage'], $formvars['innumber']));
	}
	
	static function getResult()
    {
		 if (self::$ok)
		 {
            return ui_sysmessage::shout(ui_language::translate("Your ticket has been created. We will review it as soon as possible"), "zannounceok");
        }
		if (self::$update)
		{
            return ui_sysmessage::shout(ui_language::translate("Support Ticket Updated."), "zannounceok");
        }
        return;
    }

    static function getCopyright()
	{
        $copyright = '<font face="ariel" size="2">'.ui_module::GetModuleName().' v2.0.0 &copy; 2013-'.date("Y").' Rebuilt by <a target="_blank" href="http://forums.sentora.org/member.php?action=profile&uid=2">TGates</a> for <a target="_blank" href="http://sentora.org">Sentora Control Panel</a> &#8212; Help support future development of this module and donate today!</font>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="DW8QTHWW4FMBY">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="70" height="21" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>';
        return $copyright;
    }
    /**
     * Webinterface sudo methods.
     */
}
?>