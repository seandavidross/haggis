{OVERALL_GAME_HEADER}

<div id="betpanel" class="whiteblock">
    <h3>{LB_BETTING}:</h3>
    {BET_EXPLANATION}:
    <br/>
    <br/>
    <a id="bet_nobet" href="#">[{LB_NO_BET}]</a> &bull;
    <a id="bet_littlebet" href="#">[{LB_LITTLEBET}: 15
        <div class="icon16 icon16_point"></div>]</a> &bull;
    <a id="bet_bigbet" href="#">[{LB_BIGBET}: 30
        <div class="icon16 icon16_point"></div>]</a>
</div>

<h3>{LB_MY_HAND}:
    <a href="#" class="reordercards" id="order_by_rank">[{LB_REORDER_CARDS_BY_RANK}]</a>
    <a href="#" class="reordercards" id="order_by_color" style="display:none;">[{LB_REORDER_CARDS_BY_COLOR}]</a>
</h3>

<div id="myhand">
</div>

<h3>{LB_CARDS_PLAYED}:</h3>

<div id="card_played">
</div>


<script type="text/javascript">
    // Templates
    var jstpl_player_board =
        '\<div class="ha_board">\
    <div class="icon16 icon16_hand"></div>x<span id="handcount_${id}">0</span>\
    <div class="card_point_icon capturedpoints" id="ttcard_point_icon_${id}"></div>x<span id="capturedpoints_${id}" class="capturedpoints">0</span>\
    &bull;\
        <div id="wildcard_${id}_11" class="wild_card wild_card_11"></div>\
        <div id="wildcard_${id}_12" class="wild_card wild_card_12"></div>\
        <div id="wildcard_${id}_13" class="wild_card wild_card_13"></div>\
    <div>\
        <span id="bet_${id}_no" class="bet">(-)</span>\
        <span id="bet_${id}_little" class="bet">({LB_LITTLEBET})</span>\
        <span id="bet_${id}_big" class="bet">({LB_BIGBET})</span>\
    </div>\
    </div>';

    var jstpl_combo =
        '<div id="combo_${combo_id}" class="combo">\
        <div class="comboplayer">${player_name}:</div>\
        <div id="combocards_${combo_id}" class="combocards"></div>\
    </div>';
</script>

{OVERALL_GAME_FOOTER}