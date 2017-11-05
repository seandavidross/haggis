<?php
 /**
  * haggistwo.view.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * haggistwo main static view construction
  *
  */

  require_once( APP_BASE_PATH."view/common/game.view.php" );

  class view_haggistwo_haggistwo extends game_view
  {
    function getGameName() 
    {
      return "haggistwo";
    }

    function build_page( $viewArgs )
    {
      $this->tpl['BET_EXPLANATION'] = 
          self::_("Bet you can be the first to shed all cards from hand");
    }
  }

?>
