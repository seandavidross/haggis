<?php
 /**
  * haggistwo.action.php
  *
  * @author Grégory Isabelli <gisabelli@gmail.com>
  * @copyright Grégory Isabelli <gisabelli@gmail.com>
  * @package Game kernel
  *
  *
  * haggistwo main action entry point
  *
  */
  
  
  class action_haggistwo extends APP_GameAction
  { 
    public function __default()
    {
      if( self::isArg( 'notifwindow') )
      {
        $this->view = "common_notifwindow";

        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
      }
      else
      {
        $this->view = "haggistwo_haggistwo";
        
        self::trace( "Complete reinitialization of board game" );
      }

    } 


    public function playCombo()
    {
      self::setAjaxMode(); 

      $cards_raw = self::getArg( "cards", AT_numberlist, true );

      $combo_id = self::getArg( "combo", AT_posint, false, null );
      
      // Removing last ';' if exists
      if( substr( $cards_raw, -1 ) == ';' )
        $cards_raw = substr( $cards_raw, 0, -1 );
      
      $cards 
        = $cards_raw == ''
        ? array()
        : explode( ';', $cards_raw );

      $result = $this->game->playCombo( $cards, $combo_id );

      self::ajaxResponse( );    
    }
    
    
    public function pass()
    {
      self::setAjaxMode();     
  
      $result = $this->game->pass();
  
      self::ajaxResponse( );
    }
    
    
    public function bet()
    {
      self::setAjaxMode();
  
      $bet = self::getArg( "bet", AT_enum, true, false, array( '0', '15', '30' ) );   
  
      $result = $this->game->bet( $bet );
  
      self::ajaxResponse( );
    }

  }
  
?>
