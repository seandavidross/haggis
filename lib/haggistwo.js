// Coloretto main javascript

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/scrollmap",
    "ebg/counter",
    "ebg/stock",
    "dijit/Dialog"
],
function (dojo, declare) {
    return declare("bgagame.haggistwo", ebg.core.gamegui, {
        constructor: function(){
            console.log('haggistwo constructor');
              
            this.playerHand = null;
            
            this.cardwidth = 67;
            this.cardheight = 104;
            
            this.tableCombo = {};
            this.comboChoiceDlg = null;
            this.scoreRecapDlg = null;
            

        },
        setup: function( gamedatas )
        {
            console.log( "start creating player boards" );
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                var player_board_div = $('player_board_'+player_id);
                var tpl = {id:player_id};
                dojo.place( this.format_block('jstpl_player_board', tpl ), 'player_board_'+player_id );
                 
                if( gamedatas.handcount[ player_id ] )
                {   
                    $('handcount_'+player_id).innerHTML = gamedatas.handcount[ player_id ];
                }
                else
                {
                    this.disablePlayerPanel( player_id );
                }
                                
                if( player.player_bet )
                {
                    dojo.style( $('bet_'+player_id+'_'+player.player_bet ), 'display', 'inline' );
                    if( toint(player_id) == toint( this.player_id ) )
                    {
                        dojo.style( $('betpanel'), 'display', 'none' );
                    }
                }
                
                $('capturedpoints_'+player_id).innerHTML = player.player_points_captured;
            }
            
            this.addTooltipToClass( 'capturedpoints', _('Points from cards captured during this round'), '' );
            this.addTooltipToClass( 'capturedpoints', _('Points from cards captured during this round'), '' );
            
            if( ! gamedatas.handcount[ this.player_id ] )
            {   dojo.style( $('betpanel'), 'display', 'none' ); }
            else if( gamedatas.handcount[ this.player_id ] != 17 )
            {   dojo.style( $('betpanel'), 'display', 'none' ); }
            
            this.playerHand = new ebg.stock();
            this.playerHand.create( this, $('myhand'), this.cardwidth, this.cardheight );
            this.playerHand.image_items_per_row = 10;
            dojo.connect( this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged' );
            
            // Create cards types:
            for( var serie=0;serie<=4;serie++ )
            {
                for( var value=2;value<=10;value++ )
                {
                    var card_type_id = this.getCardTypeId( serie, value );
                    this.playerHand.addItemType( card_type_id, card_type_id, g_gamethemeurl+'img/cards.png', card_type_id );
                }
            }
            this.playerHand.addItemType( 111, 111, g_gamethemeurl+'img/cards.png', 9 );
            this.playerHand.addItemType( 112, 112, g_gamethemeurl+'img/cards.png', 19 );
            this.playerHand.addItemType( 113, 113, g_gamethemeurl+'img/cards.png', 29 );

            // Player hand
            var card;
            for( var i in gamedatas.hand )
            {
                card = gamedatas.hand[i];
                this.playerHand.addToStockWithId( this.getCardTypeId( card.type, card.type_arg ), card.id );
            }
            
            // Cards on table
            for( var combo_id in gamedatas.combo )
            {
                // Get all cards from this combo
                this.addComboToTable( combo_id, gamedatas.combo[ combo_id ].player_id, gamedatas.table, gamedatas.combo[ combo_id ].display );
            }

            // Betting
            dojo.connect( $('bet_nobet'), 'onclick', this, 'onNoBet' );
            dojo.connect( $('bet_littlebet'), 'onclick', this, 'onLittleBet' );
            dojo.connect( $('bet_bigbet'), 'onclick', this, 'onBigBet' );
            
            // Wild cards
            console.log( 'wild cards' );
            for( i in gamedatas.wildcards )
            {
                card = gamedatas.wildcards[i];
                if( card.location=='hand' )
                {
                    dojo.style( $('wildcard_'+card.location_arg+'_'+card.type_arg), 'display', 'inline-block' );
                }
            }
            
            if( gamedatas.bombcheck.rainbow )
            {     this.showMessage( _('You have a rainbow bomb in your hand !'), 'info' );        }
            if( gamedatas.bombcheck.suited )
            {     this.showMessage( _('You have a suited bomb in your hand !'), 'info' );        }
            
            // Card reorder
            dojo.connect( $('order_by_rank'), 'onclick', this, 'onReorderByRank' );
            dojo.connect( $('order_by_color'), 'onclick', this, 'onReorderByColor' );

            this.setupNotifications();
            this.ensureSpecificImageLoading( ['../common/point.png'] );
        },
        
        onNoBet: function( evt )
        {
            console.log( 'onNoBet' );
            evt.preventDefault();
            this.ajaxcall( "/haggistwo/haggistwo/bet.html", { bet: 0, lock: true }, this, function( result ) {
               dojo.style( $('betpanel'), 'display', 'none' );
            } );
        },
        onLittleBet: function( evt )
        {
            console.log( 'onLittleBet' );
            evt.preventDefault();
            this.ajaxcall( "/haggistwo/haggistwo/bet.html", { bet: 15, lock: true }, this, function( result ) {
               dojo.style( $('betpanel'), 'display', 'none' );
            } );
        },
        onBigBet: function( evt )
        {
            console.log( 'onBigBet' );
            evt.preventDefault();
            this.ajaxcall( "/haggistwo/haggistwo/bet.html", { bet: 30, lock: true }, this, function( result ) {
               dojo.style( $('betpanel'), 'display', 'none' );
            } );
        },

        getCardTypeId: function( serie_id, value )
        {
            if( serie_id == 'wild' )
            {
                if( toint(value)==11 )
                {   return 111; }   // Jack
                else if( toint(value)==12 )
                {   return 112; }   // Queen
                else if( toint(value)==13 )
                {   return 113; }   // King
            }
            else
            {
                return ( toint(serie_id)*10 )+ (toint(value)-2);
            }
        },
        
        // Add this combo to table with associated player's name
        addComboToTable: function( combo_id, player_id, cards, display )
        {
            console.log( "addComboToTable" );
            console.log( cards );
            console.log( display );
            
            dojo.place( this.format_block('jstpl_combo', {
                player_name: this.gamedatas.players[player_id].name,
                combo_id: combo_id
            } ), 'card_played', 'first' );
            
            if( cards !== null && cards.length !== 0 )
            {            
                this.tableCombo[ combo_id ] = new ebg.stock();
                this.tableCombo[ combo_id ].create( this, $('combocards_'+combo_id), this.cardwidth, this.cardheight );
                this.tableCombo[ combo_id ].order_items = false;
                this.tableCombo[ combo_id ].image_items_per_row = 10;
                
                // Create cards types:
                for( var serie=0;serie<=4;serie++ )
                {
                    for( var value=2;value<=10;value++ )
                    {
                        var card_type_id = this.getCardTypeId( serie, value );
                        this.tableCombo[ combo_id ].addItemType( card_type_id, card_type_id, g_gamethemeurl+'img/cards.png', card_type_id );
                    }
                }
                this.tableCombo[ combo_id ].addItemType( 111, 111, g_gamethemeurl+'img/cards.png', 9 );
                this.tableCombo[ combo_id ].addItemType( 112, 112, g_gamethemeurl+'img/cards.png', 19 );
                this.tableCombo[ combo_id ].addItemType( 113, 113, g_gamethemeurl+'img/cards.png', 29 );

                for( var i in display )
                {
                    var card_id = display[i];
                    var card = cards[card_id];
                    if( card )
                    {                    
                        var from = $('overall_player_board_'+player_id);
                        if( player_id == this.player_id )   // Current player => came from player hand
                        {   
                            var carditem = $('myhand_item_'+card.id);
                            if( carditem )
                            {
                                from = carditem;
                                this.playerHand.removeFromStockById( card.id );
                            }
                        }
                        
                        this.tableCombo[ combo_id ].addToStockWithId( this.getCardTypeId( card.type, card.type_arg ), card.id, from );
                        
                        if( card.type == 'wild' )
                        {
                            // Remove corresponding wild cards in player's panel
                            dojo.style( $('wildcard_'+player_id+'_'+card.type_arg), 'display', 'none' );
                        }
                    }
                }
            }
            else
            {
                // This player passed
                $('combocards_'+combo_id).innerHTML = _('(pass)');
            }
        },
        
        // Clean all combo elements on the table
        cleanTable: function()
        {
            dojo.empty( 'card_played' );
            this.tableCombo = {};
        },

        ///////////////////////////////////////////////////
        //// UI events
        
        onPlayerHandSelectionChanged: function( )
        {
        
        },
        
        onPlayCards: function( )
        {
            console.log( 'onPlayCards' );
            var selected = this.playerHand.getSelectedItems();
            console.log( selected );
            
            var to_play = '';
            for( var i in selected )
            {
                to_play += selected[i].id+';';
            }

            this.ajaxcall( "/haggistwo/haggistwo/playCombo.html", { cards: to_play, lock: true }, this, function( result ) {
               // this.playerHand.unselectAll();
            }, function( is_error) {
                if( is_error )
                {    this.playerHand.unselectAll();    
                }
            } );
        },

        onComboChoice: function( evt )
        {
            console.log( 'onComboChoice' );
            evt.preventDefault();
            
            // combo_<i>
            var combo_id = evt.currentTarget.id.substr( 6 );
            var selected = this.playerHand.getSelectedItems();
            console.log( selected );
            
            var to_play = '';
            for( var i in selected )
            {
                to_play += selected[i].id+';';
            }

            this.comboChoiceDlg.hide();

            this.ajaxcall( "/haggistwo/haggistwo/playCombo.html", { cards: to_play, combo:combo_id, lock: true }, this, function( result ) {
                this.playerHand.unselectAll();
            }, function( is_error) {
                this.playerHand.unselectAll();            
            } );
        },
        
        onPass: function()
        {
            console.log( 'onPass' );
            this.ajaxcall( "/haggistwo/haggistwo/pass.html", { lock: true }, this, function( result ) {} );
        },
        
        onReorderByRank: function( evt )
        {
            console.log( 'onReorderByRank' );
            evt.preventDefault();
            var newWeights = {};
            for( var serie=0;serie<=4;serie++ )
            {
                for( var value=2;value<=10;value++ )
                {
                    var card_type_id = this.getCardTypeId( serie, value );
                    newWeights[ card_type_id ] = 10*toint( value ) + toint( serie );
                }
            }
            // We don't change the weight of face cards 
           this.playerHand.changeItemsWeight( newWeights );   
           
           dojo.style( 'order_by_rank', 'display', 'none' );                    
           dojo.style( 'order_by_color', 'display', 'inline' );                    
        },
        onReorderByColor: function( evt )
        {
            console.log( 'onReorderByColor' );
            evt.preventDefault();
            var newWeights = {};
            for( var serie=0;serie<=4;serie++ )
            {
                for( var value=2;value<=10;value++ )
                {
                    var card_type_id = this.getCardTypeId( serie, value );
                    newWeights[ card_type_id ] = card_type_id;
                }
            }
            // We don't change the weight of face cards                        
            this.playerHand.changeItemsWeight( newWeights );                       

           dojo.style( 'order_by_rank', 'display', 'inline' );                    
           dojo.style( 'order_by_color', 'display', 'none' );                    
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function( stateName, args )
        {
           console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            case 'playCombo':
                if( this.isCurrentPlayerActive() )
                {
                    this.addActionButton( 'pass', _('Pass'), 'onPass' );
                    this.addActionButton( 'playCombo', _('Play selected cards'), 'onPlayCards' );
                }
                this.playerHand.unselectAll();
                break;
            case 'playComboOpen':
                if( this.isCurrentPlayerActive() )
                {
                    this.addActionButton( 'playCombo', _('Play selected cards'), 'onPlayCards' );
                }               
                this.playerHand.unselectAll();
                break;
                
            case 'newRound':
                this.cleanTable();
                this.playerHand.removeAll();
                dojo.style( $('betpanel'), 'display', 'block' );
                dojo.query( '.bet' ).style( 'display', 'none' );
                break;

            case 'newTrick':
                this.cleanTable();
                break;
            }
        },
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
             
          //  switch( stateName )
          //  {
          //  case 'playerTurn':
              
          //      break;
          //  }                
        }, 
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            dojo.subscribe( 'playCombo', this, "notif_playCombo" );
            this.notifqueue.setSynchronous( 'playCombo', 1000 );
            
            dojo.subscribe( 'pass', this, "notif_pass" );
            dojo.subscribe( 'captureCards', this, "notif_captureCards" );
            this.notifqueue.setSynchronous( 'captureCards', 1000 );
            
            dojo.subscribe( 'newRound', this, "notif_newRound" );
            dojo.subscribe( 'newDeal', this, "notif_newDeal" );
            dojo.subscribe( 'playerGoOut', this, "notif_playerGoOut" );
            this.notifqueue.setSynchronous( 'playerGoOut', 2000 );
            dojo.subscribe( 'bet', this, "notif_bet" );
            dojo.subscribe( 'betresult', this, "notif_betresult" );
            dojo.subscribe( 'multipleCombos', this, "notif_multipleCombos" );
            dojo.subscribe( 'finalCapture', this, "notif_finalCapture" );
            dojo.subscribe( 'scoreRecap', this, "notif_scoreRecap" );
            this.notifqueue.setSynchronous( 'scoreRecap', 3000 );
        },  
        
        notif_playCombo: function( notif )
        {
            console.log( 'notif_playCombo' );
            console.log( notif );
            
            this.addComboToTable( notif.args.combo_no, notif.args.player_id, notif.args.cards, notif.args.display );
            $('handcount_'+notif.args.player_id).innerHTML = toint( $('handcount_'+notif.args.player_id).innerHTML ) - toint( notif.args.nbr );

            if( notif.args.player_id == this.player_id )
            {   dojo.style( $('betpanel'), 'display', 'none' ); }      
        },
        notif_pass: function( notif )
        {
            console.log( 'notif_pass' );
            console.log( notif );
            
            this.addComboToTable( notif.args.combo_no, notif.args.player_id, null, null );
        },
        notif_captureCards: function( notif )
        {
            console.log( 'notif_captureCards' );
            console.log( notif );
            
            var player_id = notif.args.player_id;
            var to = $('overall_player_board_'+player_id );
            
            // All cards => to this player
            for( var combo_id in this.tableCombo )
            {
                var items = this.tableCombo[combo_id].getAllItems();
                for( var i in items )
                {
                    var item = items[i];
                    var card_id = item.id; 
                    this.tableCombo[combo_id].removeFromStockById( card_id, to );
                }             
            }
            
            // we increase score for captured cards at the end of the round
            //this.scoreCtrl[ player_id ].incValue( notif.args.score );
            $('capturedpoints_'+player_id).innerHTML = ( toint( $('capturedpoints_'+player_id).innerHTML ) + notif.args.score );
        },
        notif_newRound: function( notif )
        {
            console.log( 'notif_newRound' );
            console.log( notif );

            this.enableAllPlayerPanels();

            // All players => 17 cards and all wild cards
            for( var player_id in this.gamedatas.players )
            {
                $('handcount_'+player_id).innerHTML = '17';
                dojo.style( $('wildcard_'+player_id+'_'+11), 'display', 'inline-block' );
                dojo.style( $('wildcard_'+player_id+'_'+12), 'display', 'inline-block' );
                dojo.style( $('wildcard_'+player_id+'_'+13), 'display', 'inline-block' );
                $('capturedpoints_'+player_id).innerHTML = 0;
            }

        },        
        notif_newDeal: function( notif )
        {
            console.log( 'notif_newDeal' );
            console.log( notif );
            
            this.playerHand.removeAll();
            
            // Player hand
            for( var i in notif.args.cards )
            {
                var card = notif.args.cards[i];
                this.playerHand.addToStockWithId( this.getCardTypeId( card.type, card.type_arg ), card.id );
            }        
            
            if( notif.args.bombcheck.rainbow )
            {     this.showMessage( _('You have a rainbow bomb in your hand !'), 'info' );        }
            if( notif.args.bombcheck.suited )
            {     this.showMessage( _('You have a suited bomb in your hand !'), 'info' );        }
            
        },
        notif_playerGoOut: function( notif )
        {
            console.log( 'notif_playerGoOut' );
            console.log( notif );
            
            this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.score );
            this.disablePlayerPanel( notif.args.player_id );
        },
        notif_betresult: function( notif )
        {
            console.log( 'notif_betresult' );
            console.log( notif );
            
            if( notif.args.targets )
            {
                for( var i in notif.args.targets )
                {
                    this.scoreCtrl[ notif.args.targets[i] ].incValue( notif.args.points );
                }
            }
            else
            {
                this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.points );
            }
        },
        notif_bet: function( notif )
        {
            console.log( 'notif_bet' );
            console.log( notif );
            
            dojo.style( $('bet_'+notif.args.player_id+'_'+notif.args.bet), 'display', 'inline' );
            if( toint( notif.args.player_id ) == toint( this.player_id ) )
            {   dojo.style( $('betpanel'), 'display', 'none' ); }
        },
        notif_finalCapture: function( notif )
        {
            console.log( 'notif_finalCapture' );
            console.log( notif );  
            
            //this.scoreCtrl[ notif.args.player_id ].incValue( notif.args.score );
            $('capturedpoints_'+notif.args.player_id).innerHTML = ( toint( $('capturedpoints_'+notif.args.player_id).innerHTML ) + notif.args.score );
        },
        notif_multipleCombos: function( notif )
        {
            console.log( 'notif_multipleCombos' );
            console.log( notif );
            
            // Can choose among multiple combos
            if( $('comboChoiceDlgContent') )
            {   dojo.destroy( 'comboChoiceDlgContent' );    }
            
            this.comboChoiceDlg = new dijit.Dialog({
                title: _("Several combination can be made with these cards")
            });
            this.comboChoiceDlg.closeButtonNode.style.display = "none";
            
            var html = '<div id="comboChoiceDlgContent">';
            html += '<p>'+_("Choose")+':</p>';
            
            for( var i in notif.args )
            {
                var combo = notif.args[i];
                console.log( combo );
                if( combo.type=='set' )
                {
                    html += '<p><a href="#" id="combo_'+i+'" class="comboChoice">' + dojo.string.substitute( _('A set of ${nbr} cards'), {nbr:combo.nbr} ) + '</a></p>';
                }
                else if( combo.type=='sequence' )
                {
                    html += '<p><a href="#" id="combo_'+i+'" class="comboChoice">' + dojo.string.substitute( _('${serienbr} sequences of ${nbr} cards'), {serienbr:combo.serienbr, nbr:(combo.nbr/combo.serienbr)} ) + '</a></p>';                
                }
            }
            
            html += '</div>';
            
            this.comboChoiceDlg.attr("content", html );
            this.comboChoiceDlg.show();

           dojo.query( '.comboChoice').connect( 'onclick', this, 'onComboChoice' );            
        },
        
        // Display the score recap' dialog
        notif_scoreRecap: function( notif )
        {
            console.log( 'scoreRecap' );
            console.log( notif );

            if( $('scoreRecapDlgContent') )
            {   dojo.destroy( 'scoreRecapDlgContent' ); }
            
            this.scoreRecapDlg = new dijit.Dialog({
                title: _("End of the round")
            });
            
            var html = '<div id="scoreRecapDlgContent">';
            html += '<table><tr>';
            html += '<th></th>';
            html += '<th>'+_('Bets')+'</th>';
            html += '<th>'+_('Captured cards')+'</th>';
            html += '<th>'+_('Remaining cards')+'</th>';
            html += '<th>'+_('Total')+'</th>';
            html += '</tr>';
            
            for( var player_id in notif.args )
            {
               var player = notif.args[player_id];
               var total = toint( player.player_points_bet ) + toint( player.player_points_captured ) + toint( player.player_points_remaining );
               html += '<tr><th>'+this.gamedatas.players[player_id].name+'</th>';
               html += '<td>'+player.player_points_bet+'</td>';
               html += '<td>'+player.player_points_captured+'</td>';
               html += '<td>'+player.player_points_remaining+'</td>';
               html += '<th>'+total+'</th>';
               html += '</tr>';
               
               this.scoreCtrl[ player_id ].toValue( player.player_score );
            }
            
            html += '</table></div>';
            
            this.scoreRecapDlg.attr("content", html );
            this.scoreRecapDlg.show();
        }
    });
});


