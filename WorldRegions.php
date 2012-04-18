<?php
/***************************************************************************\
    This file is part of RoomAllocation.

    RoomAllocation is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
\***************************************************************************/
?>
<?
  $WorldRegions = array(
    'North America'       => array('Canada','United States', 'USA'),
    'Latin America'       => array('Argentina','Bahamas','Barbados','Belize','Bolivia','Brazil','Chile','Colombia','Costa Rica','Cuba','Dominican Republic','Ecuador','El Salvador','Grenada','Guatemala','Guyana','Haiti','Honduras','Jamaica','Mexico','Nicaragua','Panama','Paraguay','Peru','Saint Vincent & the Grenadines','St Kitts & Nevis','St Lucia','Suriname','Trinidad & Tobago','Trinidad and Tobago','Uruguay','Venezuela'),
    'Western Europe'      => array('Andorra','Austria','Belgium','Cyprus','Denmark','Finland','France','Germany','Greece','Iceland','Ireland','Ireland {Republic}','Italy','Liechtenstein','Luxembourg','Malta','Monaco','Netherlands','Norway','Portugal','San Marino','Spain','Sweden','Switzerland','United Kingdom','Vatican City'),
    'Eastern Europe'      => array('Albania','Armenia','Belarus','Bosnia Herzegovina','Bulgaria','Croatia','Czech Republic','Estonia','Georgia','Hungary','Kazakhstan','Kyrgyzstan','Latvia','Lithuania','Macedonia','Moldova','Montenegro','Poland','Romania','Russian Federation','Russia','Serbia','Slovakia','Slovenia','South Kosovo','Kosovo','Tajikistan','Turkmenistan','Ukraine','Uzbekistan'),
    'West/Central Africa' => array('Benin','Burkina','Cameroon','Cape Verde','Central African Rep','Chad','Congo','Congo {Democratic Rep}','Equatorial Guinea','Gabon','Gambia','Ghana','Guinea','Guinea-Bissau','Ivory Coast','Liberia','Mali','Mauritania','Niger','Nigeria','Sao Tome & Principe','Senegal','Sierra Leone','Togo'),
    'South/East Africa'   => array('Angola','Botswana','Burundi','Comoros','Djibouti','Eritrea','Ethiopia','Kenya','Lesotho','Madagascar','Malawi','Mauritius','Mozambique','Namibia','Rwanda','Seychelles','Somalia','South Africa','Swaziland','Tanzania','Uganda','Zambia','Zimbabwe'),
    'Middle East'         => array('Algeria','Azerbaijan','Bahrain','Egypt','Iran','Iraq','Israel','Jordan','Kuwait','Lebanon','Libya','Morocco','Sudan','North Sudan','Oman','Palestine','Qatar','Saudi Arabia','Syria','Tunisia','Turkey','United Arab Emirates','Yemen'),
    'East Asia'           => array('Brunei','Cambodia','China','East Timor','Indonesia','Japan','South Korea','Korea','Laos','Malaysia','Mongolia','North Korea','Philippines','Singapore','Taiwan','Thailand','Tibet','Vietnam'),
    'South-East Asia'     => array('Afghanistan','Bangladesh','Bhutan','India','Maldives','Myanmar','Nepal','Pakistan','Sri Lanka'),
    'Australia + Oceania' => array('Australia')
  );
  
  $WorldRegions_Inv = array();
  
  foreach( $WorldRegions as $k => $v ){
    foreach( $v as $country ){
      $WorldRegions_Inv[$country] = $k;
    }
  }
  
?>