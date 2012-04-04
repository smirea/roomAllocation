<?php
  
  function getFaceHTML( $info, $append = '' ){
    foreach( $info as $k => $v ){ $$k = $v; }
      $img            = imageUrl( $eid );
      $country_flag   = flagURL( $country );
      $d              = 3-((2000+(int)$year)-(int)date("Y"));
      $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
      return <<<HTML
        <table class="face" cellspacing="0" cellpadding="0" id="face-eid-$eid">
          <tr>
            <td rowspan="3"><img src="$img" height="64" class="photo" /></td>
            <td style="width:100%"><b>$fname, $lname</b></td>
            <td rowspan="3">
              <img height="64" alt="$country" src="$country_flag">
            </td>
          </tr>
          <tr><td>class of 20$year ($year_of_study year)</td></tr>
          <tr>
            <td>$country</td>
          </tr>
          $append
        </table>
HTML;
  }
  
  function getFaceHTML_received( $info, $append = '' ){
    $actions = '
      <tr class="actions">
        <td colspan="3" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center">
          <div class="gh-button-group">
            <a href="javascript:void(0)" onclick="sendResponse(\'requestReceived\',\''.$info['eid'].'\',\'yes\')" class="gh-button pill primary safe icon approve">accept</a>
            <a href="mailto:'.$info['email'].'" class="gh-button pill icon mail">send email</a>
            <a href="javascript:void(0)"  onclick="sendResponse(\'requestReceived\',\''.$info['eid'].'\',\'no\')" class="gh-button pill danger icon remove">reject</a>
          </div>
        </td>
      </tr>
    ';
    return getFaceHTML( $info, $actions.$append );
  }
  
  function getFaceHTML_sent( $info, $append = '' ){
    $actions = '
      <tr class="actions">
        <td colspan="3" style="padding:3px;border-top:1px solid #999;background:#fff;text-align:center">
          <div class="gh-button-group">
            <a href="mailto:'.$info['email'].'" class="gh-button pill icon mail">send email</a>
            <a href="javascript:void(0)" onclick="sendResponse(\'requestSent\',\''.$info['eid'].'\',\'no\')" class="gh-button pill danger icon remove">cancel request</a>
          </div>
        </td>
      </tr>
    ';
    return getFaceHTML( $info, $append.$actions );
  }
  
?>