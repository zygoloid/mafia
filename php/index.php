<?
header("Pragma: no-cache");
header("Cache-Control: no-cache");

define(MAFIA_EXEC_PATH,'/var/www/sites/metafoo.co.uk/htdocs/php/exec/mafia');
// define(MAFIA_EXEC_PATH,'echo ');

function mafiaId()
{
 global $HTTP_GET_VARS;
 $id = $HTTP_GET_VARS['id'];
 if(ereg('^[0-9A-Za-z]+$', $id))
  return $id;
 return '';
}

function archive()
{
 return '';
 return section
 (
  'mafia game archive', 'mafia',
  paragraph( "sorry, i've not sorted this out yet." )
 );
}

function callMafia($gameid, $uid, $mafcmd)
{
 $args = func_get_args();
 $command = MAFIA_EXEC_PATH . " $mafcmd --gameid $gameid";
 if( strlen($uid) > 0 )
  $command .= " --uid $uid";
 unset($args[0]);
 unset($args[1]);
 unset($args[2]);
 foreach( $args as $arg )
  $command .= " $arg";
 $result = Array();
 $errorcode = 0;
//  $command .= ' 2>&1';
//  print $command;
 exec( $command, $result, $errorcode );
 if( $errorcode != 0 )
 {
  $output = '';
  foreach( $result as $line )
   $output .= $line . '<br />';
  page
  (
   "Mafia: Error!","games/mafia","",
   mafiaError( "Sorry, an error occurred processing your request.<br />Command: $command<br />Result: $errorcode<br />Output:<br />$output" )
  );
  exit();
 }
 return $result;
}

function mafiaError($err)
{
 return section
 (
  'mafia - error', 'mafia',
  paragraph
  (
   '<b>' . $err . '</b><br />would you like to go to the ' .
   locallink('/games/mafia?id=current','current game') . '?'
  )
 )
 .
 archive();
}

function mafiaUserNameDisplay($gameid, $uid, $player)
{
 return userDisplay($player);
}

function mafiaUserDisplay($gameid, $uid, $player, $details)
{
 $type = mafiaGetType($details);
 return "<a href=\"/wiki/Mafia:PlayerTypes/" . ucwords($type) . "\"><img src=\"/images/mafia/$type.png\" alt=\"$type\" /></a><br />" .
        mafiaUserNameDisplay($gameid, $uid, $player);
}

function mafiaUserPicture($gameid, $uid, $player, $details)
{
 $deadOverlay = userPicture($player);
 if( isDead( $details ) )
  $deadOverlay = themedUserPicture($player,'dead');
 return "<a href=\"/userdetails/?user=$player\">" . $deadOverlay . '</a>';
}

function contains($array,$firstword)
{
 foreach($array as $item)
  if(strstr($item,$firstword) == $item)
   return True;
 return False;
}

function containsWithArg($array,$firstword,&$result)
{
 foreach($array as $item)
  if(strstr($item,$firstword) == $item)
  {
   $result = trim(substr($item,strlen($firstword)));
   return True;
  }
 return False;
}

function visibleVote($array,$vote,$prologueHas)
{
 if( containsWithArg($array,"they_vote_$vote",$target) )
 {
  if( strlen($target) > 0 )
   return "$vote: $target<br />";
  else
   return "$vote: (undecided)<br />";
 }
 return '';
}

function mafiaGetType($details)
{
 $type = 'unknown';
 if( contains($details,townsperson) )
  $type = 'townsperson';
 if( contains($details,doctor) )
  $type = 'doctor';
 if( contains($details,force) )
  $type = 'detective';
 if( contains($details,mafia) )
  $type = 'mafia';
 if( contains($details,checked_inmafia) )
  $type = 'mafia';
 return $type;
}

function votesFor($detailArray, $player)
{
 $votes = 0;
 foreach( $detailArray as $details )
  if( contains($details,"they_vote_lynch $player") )
   $votes++;
  return $votes;
}

function mafiaUserDetails($gameid, $uid, $player, $details, $detailArray)
{
 $votesFor = votesFor($detailArray, $player);
 $prologueIs = ($player == $uid) ? 'you are' : 'this player is';
 $prologueHas = ($player == $uid) ? 'you have' : 'this player has';
 $is = ($player == $uid) ? 'are' : 'is';
 $result = '';
//  foreach( $details as $detail )
//   $result .= "$detail<br />";

 $type = mafiaGetType($details);
 if( contains($details,lynched) )
  $result .= "<span style=\"color: red;\"><b>$prologueIs dead (lynched)</b></span><br />";
 if( contains($details,murdered) )
  $result .= "<span style=\"color: red;\"><b>$prologueIs dead (murdered)</b></span><br />";
 if( contains($details,checked_inmafia) )
  $result .= "$prologueHas been checked and <b>$is</b> in the mafia.<br />";
 if( contains($details,checked_notinmafia) )
  $result .= "$prologueHas been checked and $is not in the mafia.<br />";
 if( contains($details,you_vote_lynch) )
  $result .= "you have voted to lynch this player<br />";
 if( contains($details,you_vote_murder) )
  $result .= "you have voted to murder this player<br />";
 if( contains($details,you_vote_save) )
  $result .= "you have voted to save this player from being mafia'd in the night<br />";
 if( contains($details,you_vote_investigate) )
  $result .= "you have voted to investigate this player for mafian tendencies<br />";
 
 $votes = visibleVote($details,lynch,$prologueHas) . visibleVote($details,murder,$prologueHas) . visibleVote($details,save,$prologueHas) . visibleVote($details,investigate,$prologueHas);
 
 if($result != '') $result = "<div>$result</div>";
 $result .= "<table width=\"100%\"><tr><td><b>type:</b></td><td width=\"100%\">$type</td>";
 if( $votes != '' )
  $result .= "</tr><tr><td><b>votes:</b></td><td width=\"100%\">$votes</td>";
 if( $votesFor > 0 )
  $result .= "<td><b>votes&nbsp;to&nbsp;lynch:</b></td><td>$votesFor</td>";
 return $result . "</tr></table>";
}

function mafiaUserActions($gameid, $uid, $player, $actions)
{
 $result = '';
 foreach( $actions as $action )
 {
  $actionName = Array( vote_lynch => 'lynch', vote_murder => 'murder', vote_save => 'save', vote_investigate => 'investigate',
                       unvote_lynch => 'don\'t lynch', unvote_murder => 'don\'t murder', unvote_save => 'don\'t save', unvote_investigate => 'don\'t investigate' );
  $name = $actionName[$action];
  $result .= "<form method=\"post\" action=\"?id=$gameid\"><input type=\"hidden\" name=\"action\" value=\"$action\" />";
  $result .= "<input type=\"hidden\" name=\"target\" value=\"$player\" /><input type=\"submit\" value=\"$name\" /></form>";
 }
 return $result;
}

function mafiaDayBanner($day, $phase)
{
 return "<h2>round $day: $phase phase</h2>\n";
}

function isDead($details)
{
 return contains($details, lynched) || contains($details, murdered);
}

function mafiaStatsToday($gameid, $uid, $showDead)
{
 $players = callMafia($gameid, $uid, players);
 sort($players);
 $result = '<table width="100%">' . "\n";
 $detailArray = Array();
 foreach($players as $player)
 {
  $detailArray[$player] = callMafia($gameid, $uid, player_details, $player);
 }
 foreach($players as $player)
 {
  $details = $detailArray[$player];
  if( ! $showDead && isDead($details) )
   continue;
  $actions = callMafia($gameid, $uid, player_actions, $player);
  $result = $result .
  ' <tr class="mafiaPlayerStats">' . "\n" .
  '  <td class="mafiaUserPicture">' . mafiaUserPicture($gameid, $uid, $player, $details) . "</td>\n" .
  '  <td class="mafiaPlayerName">' . mafiaUserDisplay($gameid, $uid, $player, $details) . "</td>\n" .
  '  <td class="mafiaPlayerDetails">' . mafiaUserDetails($gameid, $uid, $player, $details, $detailArray) . "</td>\n" .
  '  <td class="mafiaPlayerActions">' . mafiaUserActions($gameid, $uid, $player, $actions) . "</td>\n" .
  " </tr>\n";
 }
 return $result . "</table>\n";
}

function mafiaViewPastLink($gameid)
{
 return "<p align=\"right\"><a href=\"/games/mafia/?id=$gameid&amp;view=past\">index of past events</a></p>";
}

function mafiaAdminLinks($gameid, $uid)
{
  $result = '';
  if (authorized($uid, 'mafia', 'set_current'))
  {
    $result .= "<form method=\"post\" action=\"?id=$gameid\">";
    $result .= "<input type=\"hidden\" name=\"action\" value=\"set_current\" />";
    $result .= "<input type=\"submit\" value=\"set current\" /></form>";
  }
  if (authorized($uid, 'mafia', 'next_round'))
  {
    $result .= "<form method=\"post\" action=\"?id=$gameid\">";
    $result .= "<input type=\"hidden\" name=\"action\" value=\"next_round\" />";
    $result .= "<input type=\"submit\" value=\"next round\" /></form>";
  }
  return $result;
}

function mafiaViewMain($gameid, $uid)
{
 global $HTTP_GET_VARS;
 global $HTTP_COOKIE_VARS;
 $showDead = $HTTP_GET_VARS[showdead];
 if( isSet($showDead) )
  setCookie('mafia:showdead', $showDead);
 if( !isSet($showDead) && isSet($HTTP_COOKIE_VARS['mafia:showdead']) )
  $showDead = $HTTP_COOKIE_VARS['mafia:showdead'];
 if( isSet($showDead) && ($showDead == '0' || $showDead == 'no') )
  $showDead = false;
 else
  $showDead = true;

 $winner = callMafia($gameid, $uid, winner);
 $sectionName = "mafia: state of play";
 if( count($winner) > 0 )
  $sectionName = "mafia: $winner[0] have won";
 
 list($today, $thisphase) = callMafia($gameid, $uid, 'round');

 $body = mafiaDayBanner($today, $thisphase);
 if($showDead)
  $body .= "<p align=\"right\"><a href=\"/games/mafia/?id=$gameid&amp;showdead=no\">hide dead players</a></p>";
 else
  $body .= "<p align=\"right\"><a href=\"/games/mafia/?id=$gameid&amp;showdead=yes\">show dead players</a></p>";
 $body .= mafiaStatsToday($gameid, $uid, $showDead) . mafiaViewPastLink($gameid) . mafiaAdminLinks($gameid, $uid);
 return section( $sectionName, '', $body );
}

function mafiaViewPast($gameid, $uid)
{
 list($today, $thisphase) = callMafia($gameid, $uid, 'round');
 
 $body = '';
 for($day = 1; $day <= $today; $day++)
 {
  $body .= "<h3>day $day</h3>";
  if($day != $today || $thisphase != 'day')
   $body .= "<a href=\"/games/mafia/?id=$gameid&amp;view=votes&amp;day=$day\">view lynching votes</a>";
 }
 
 return section( 'mafia: the past', '', $body );
}

function mafiaViewVotes($gameid, $uid)
{
 global $HTTP_GET_VARS;
 $day = $HTTP_GET_VARS[day];
 if( !isSet($day) || !is_numeric($day) )
  return mafiaError('no day specified');
 $day = intval($day);
 
 $votes = callMafia($gameid, $uid, lynch_votes, $day);
 $votesFor = Array();
 foreach( $votes as $vote )
 {
  list($voter, $target) = split(' ',$vote,2);
  if( isSet( $votesFor[$target] ) )
   $votesFor[$target]++;
  else
   $votesFor[$target] = 1;
 }
 
 arsort($votesFor, SORT_NUMERIC);
 foreach( $votesFor as $victim => $voteCount )
 {
  $s = ($voteCount > 1 ? 's' : '');
  $body .= '<h3>' . mafiaUserNameDisplay($gameid, $uid, $victim) . ": $voteCount vote$s</h3><div>";
  foreach( $votes as $vote )
  {
   list($voter, $target) = split(' ',$vote,2);
   if( $target == $victim )
    $body .= mafiaUserNameDisplay($gameid, $uid, $voter) . '<br />';
  }
  $body .= "</div>";
 }

 $body .= mafiaViewPastLink($gameid);
  
 return section( "mafia: lynch votes for day $day", '', $body );
}

function mafiaStats($gameid, $uid)
{
 global $mafiaPhases;
 global $HTTP_GET_VARS;
 $view = $HTTP_GET_VARS[view];
 if( !isSet($view) )
  $view = 'main';

 if( $view == 'main' )
  return mafiaViewMain($gameid, $uid);
 else if( $view == 'past' )
  return mafiaViewPast($gameid, $uid);
 else if( $view == 'votes' )
  return mafiaViewVotes($gameid, $uid);
 else
  return section( 'mafia: error', '', 'current view mode is not understood' );
}

function mafia($id)
{
  if( $id == '' ) $id = 'current';
  $uid = getLoginUID();

  global $HTTP_POST_VARS;
  $action = $HTTP_POST_VARS[action];
  $target = $HTTP_POST_VARS[target];
  if(ereg('^[a-z_]+$',$action) && authorized($uid, 'mafia', $action))
  {
    header("Location: /games/mafia?id=$id");
    if($action == 'next_round')
    {
      callMafia($id,$uid,next_round);
    }
    else if($action == 'set_current')
    {
      // Doesn't work due to open_basedir. Reimplement in python?
      $baseDir = '/var/www/sites/metafoo.co.uk/data/mafia/';
      $current = $baseDir . 'current';
      $new = $baseDir . $id;
      if (is_link($current) && $current != $new)
      {
        // Let's hope no-one replaces the link with a real file now!
        unlink($current);
        symlink($baseDir + $id, $current);
      }
    }
    else if(ereg('^[a-z]+$',$target))
    {
      callMafia($id,$uid,player_action,$target,$action);
    }
    exit();
  }

  return mafiaStats($id, $uid);
}

page
(
 "Mafia!","games/mafia","",
 mafia( mafiaId() )
);

?>
