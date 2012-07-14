<?

/*
 * To be run from the scheduler to award badges
 *  forumwhore,Masterwhore,legendaryMasterwhore
 * 
 * requests
 * 
 * 
 */


$DB->query("SELECT BadgeID, Badge, Rank, Title, Action, SendPM, Value, CategoryID, Description, Image 
              FROM badges_auto AS ba 
              JOIN badges AS b ON b.ID=ba.BadgeID
             WHERE ba.Active=1
          ORDER BY b.Sort");
$AutoActions = $DB->to_array();

//echo count($AutoActions). " Automatic Awards in schedule.\n";

foreach($AutoActions as $AutoAction) {
    list($BadgeID, $Badge, $Rank, $Name, $Action, $SendPM, $Value, $CategoryID, $Description, $Image) = $AutoAction;
    
    $SQL = false;
    $NOTIN =  "u.ID NOT IN (SELECT DISTINCT u2.ID 
                                        FROM users_main AS u2 
                                        JOIN users_badges AS ub ON u2.ID = ub.UserID
                                        JOIN badges AS b ON b.ID=ub.BadgeID
                                       WHERE ub.BadgeID = $BadgeID
                                          OR (b.Badge='$Badge' AND b.Rank>=$Rank))";
            
    switch($Action){ // count things done by user
        case 'NumComments':
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN torrents_comments AS tc ON tc.AuthorID=u.ID
                     WHERE Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(tc.ID)>=$Value";
            break;
        
        case 'NumPosts':
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN forums_posts AS fp ON fp.AuthorID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(fp.ID)>=$Value";
            break;
        
        case 'RequestsFilled':
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN requests AS r ON r.FillerID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(r.ID)>=$Value";
            break;
        
        case 'UploadedTB':
            $Value *= 1099511627776;    // = 1024 * 1024 * 1024 * 1024; // value is in TB
            $SQL = "SELECT u.ID FROM users_main AS u
                     WHERE u.Enabled='1'
                       AND $NOTIN
                       AND u.Uploaded>='$Value'";
            break;
        
        case 'DownloadedTB':
            $Value *= 1099511627776;   
            $SQL = "SELECT u.ID FROM users_main  AS u
                     WHERE u.Enabled='1'
                       AND $NOTIN
                       AND u.Downloaded>='$Value'";
            break;
        
        case 'NumUploaded':
            if($CategoryID >0) { // category specific awards
                $SQL = "SELECT u.ID FROM users_main AS u 
                     LEFT JOIN torrents AS t ON t.UserID=u.ID 
                     LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID 
                         WHERE u.Enabled='1'
                           AND tg.NewCategoryID='$CategoryID'
                           AND $NOTIN
                         GROUP BY u.ID 
                        HAVING Count(t.ID)>=$Value";
            } else {           // count all torrents
                $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN torrents AS t ON t.UserID=u.ID 
                         WHERE u.Enabled='1'
                           AND $NOTIN
                         GROUP BY u.ID 
                        HAVING Count(t.ID)>=$Value";
            }
            break;
        
        case 'NumNewTags': // unique tags
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN tags AS t ON t.UserID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(t.ID)>=$Value";
            break;
        
        case 'NumTags': // tags added to torrents
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN torrents_tags AS t ON t.UserID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(t.ID)>=$Value";
            break;
        
        case 'NumTagVotes':
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN torrents_tags_votes AS t ON t.UserID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Count(t.ID)>=$Value";
            break; 
       
        
        case 'MaxSnatches': // of a torrent this user uploaded
            $SQL = "SELECT u.ID FROM users_main AS u LEFT JOIN torrents AS t ON t.UserID=u.ID
                     WHERE u.Enabled='1'
                       AND $NOTIN
                     GROUP BY u.ID 
                    HAVING Max(t.Snatched)>=$Value";
            break;
    }
    
    
    if ($SQL){
        
        $DB->query($SQL);
        
        $UserIDs = $DB->collect('ID');
        $CountUsers = count($UserIDs);
        
        echo "Awarding $Name ($Badge/$Rank) to $CountUsers users...\n";
    
        if ($CountUsers > 0) {
 
            $SQL_IN = implode(',',$UserIDs);

            $DB->query("UPDATE users_info SET AdminComment = CONCAT('".sqltime()." - Badge ". db_string($Name)." ". db_string($Description)."by Scheduler\n', AdminComment) WHERE UserID IN ($SQL_IN)");

		$Values = "('".implode("', '".$BadgeID."', '".db_string($Description)."'), ('", $UserIDs)."', '".$BadgeID."', '".db_string($Description)."')";
            $DB->query("INSERT INTO users_badges (UserID, BadgeID, Description) VALUES $Values");
 
            // remove lower ranked badges of same badge set
            $DB->query("DELETE ub 
                          FROM users_badges AS ub
                     LEFT JOIN badges AS b ON b.ID=ub.BadgeID 
                         WHERE ub.UserID IN ($SQL_IN) 
                           AND b.Badge='$Badge' AND b.Rank<$Rank");
            
            // IF we want to send users pm's when they get an award we should do it here, 
            // BUT it means looping through each user and sending the pm 
            // (no way to shortcut afaics because you need the inserted conv_id to link message and conversation) 
            if ($SendPM){
                foreach($UserIDs as $UserID) {
                    send_pm($UserID, 0, "Congratulations you have been awarded the $Name", 
                            "[center][br][br][img]http://".SITE_NAME.'/'.STATIC_SERVER."common/badges/{$Image}[/img][br][br][size=5][color=white][bg=#0261a3][br]{$Description}[br][br][/bg][/color][/size][/center]");
                }
            }
        }
    }
                
}  // end foreach auto actions


?>
