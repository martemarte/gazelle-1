 <?
/************************************************************************
//------------// Main friends page //----------------------------------//
This page lists a user's friends. 

There's no real point in caching this page. I doubt users load it that 
much.
************************************************************************/

// Number of users per page 
define('FRIENDS_PER_PAGE', '20');



show_header("User Groups",'jquery');
 


list($Page,$Limit) = page_limit(FRIENDS_PER_PAGE);

// Main query
$DB->query("SELECT 
	g.ID,
	g.Name,
	g.Comment,
	g.Log,
	Count(u.ID)
	FROM groups AS g
	LEFT JOIN users_groups AS u ON u.GroupID=g.ID
	GROUP BY g.ID");

//$Groups = $DB->to_array(false, MYSQLI_BOTH);


// Start printing stuff
?>
<div class="thin">
 
    <div class="head">Groups</div>
    <div class="box pad">
        <p>Usergroups are a way to keep track of a group of users. They can only be seen by staff and are not visible to any users.</p>
        <p>You can use them to carry out group actions like mass PM / promotions / awards etc.</p>
    </div>
	  
    <table>
        <tr>
            <td colspan="3" class="colhead">Add group</td>
        </tr>
        <tr class="colhead">
                <td width="90%">Name</td>
                <td width="120px"></td>
        </tr> 
        <tr class="rowa">
            <form action="groups.php" method="post">
                <input type="hidden" name="applyto" value="group" />
                <input type="hidden" name="action" value="add" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <td>
                      <input class="long" type="text" name="name" />
                </td>
                <td>
                      <input type="submit" value="Add" />
                </td>
            </form>
        </tr>
    </table>
    <br/>
    <table>
        <tr class="colhead">
                <td width="90%" colspan="2">Group</td>
                <td width="120px"></td>
        </tr>
<? 
if ($DB->record_count()==0){
?>
        <tr>
            <td colspan="3">No groups</td>
        </tr>
<?
} else {
    
//$Row = 'b';//
    while(list($ID, $Name, $Comment, $Log, $Count) = $DB->next_record()){  
        $Row = ($Row === 'a' ? 'b' : 'a');
?>
        <tr class="row<?=$Row?>">
            <form action="groups.php" method="post">
                <input type="hidden" name="applyto" value="group" />
                <input type="hidden" name="groupid" value="<?=$ID?>" />
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
                <td class="center" width="70%">
                    <h3><a href="groups?groupid=<?=$ID?>"><?=display_str($Name)?></a></h3>
                </td> 
                <td width="20%">
                    <?=$Count?> members
                </td>
                <td width="120px">
                    <input type="submit" value="Delete" />
                </td>
            </form>
        </tr>
<?  }
}      ?>
    </table>
 
    
<script type="text/javascript">
        function Toggle_All(open){
            if (open) {
                $('.friendinfo').show(); // weirdly the $ selector chokes when trying to set multiple elements innerHTML with a class selector
                jQuery('.togglelink').html('(Hide)');
            } else {
                $('.friendinfo').hide();
                jQuery('.togglelink').html('(View)'); 
            }
            return false;
        }
</script>
<?
  // end else has results
?>
</div>
<?
show_footer();
?>