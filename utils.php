<?php
  
  require_once 'WorldRegions.php';
  
  function print_score( array $people ){
    global $WorldRegions;
    global $WorldRegions_Inv;
    $year               = ((int)date('Y')) % 100;
    $h                  = '';
    $countries          = array();
    $individual_points  = 0;
    $h .= '<table class="points" cellspacing="0" cellpadding="0">';
    $h .= '<tr><td colspan="2" class="section">Individual points</td></tr>';
    foreach( $people as $v ){
      $p = min(2, max(1, 3-($v['year']-$year) ) );
      $countries[$v['country']] = true;
      $individual_points += $p;
      $h .= "<tr><td>${v['fname']}, ${v['lname']}</td><td class=\"value\">$p</td></tr>";
    }
    $country_points = count($countries) > 1 ? count($countries) : 0;
    $world_regions  = array_map(function($v){global $WorldRegions_Inv; return $WorldRegions_Inv[$v];}, array_keys($countries));
    $world_regions  = array_unique( $world_regions );
    $world_regions  = count( $world_regions ) * 0.5;
    $world_regions  = $world_regions > 0.5 ? $world_regions : 0;
    $points = $individual_points + $country_points + $world_regions;
    $h .= '<tr><td colspan="2" class="section">Bonus points</td></tr>';
    $h .= '<tr><td>Nationalities</td><td class="value">'.$country_points.'</td></tr>';
    $h .= '<tr><td>World Regions</td><td class="value">'.$world_regions.'</td></tr>';
    $h .= '<tr><td class="section">Total</td><td class="value">'.$points.'</td></tr>';
    $h .= '</table>';
    return $h;
  }
  
  function getFaceHTML( $info, $append = '' ){
    foreach( $info as $k => $v ){ $$k = $v; }
      $img            = imageUrl( $eid );
      $country_flag   = flagURL( $country );
      $d              = 3-((2000+(int)$year)-(int)date("Y"));
      $year_of_study  = $d."<sup>".($d==1?'st':($d==2?'nd':($d==3?'rd':'th')))."</sup>";
      return <<<HTML
        <table class="face" cellspacing="0" cellpadding="0" id="face-eid-$eid">
          <tr>
            <td rowspan="3" class="photo"><img src="$img" height="64" /></td>
            <td class="name"><b>$fname, $lname</b></td>
            <td rowspan="3" class="country-photo">
              <img height="64" alt="$country" src="$country_flag">
            </td>
          </tr>
          <tr>
            <td class="year">class of 20$year ($year_of_study year)</td>
          </tr>
          <tr>
            <td class="country">$country</td>
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