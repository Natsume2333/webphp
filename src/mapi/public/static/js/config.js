var apictx = "http://app.ipaychat.com/";
var websitectx = "http://www.ipaychat.com/";

if (/Android|webOS|iPhone|Windows Phone|iPod|BlackBerry|SymbianOS/i.test(window.navigator.userAgent) && !/[\?&]pc(?:[=&].*|$)/.test(window.location.href)) {
    window.location.href = websitectx+'index_m_new.html';
}

function saveSessionUuid(uuid){
    var result = $.ajax({url:"recharge!saveSessionUuid.jhtml?uuid="+uuid,async:false});
}

function getSessionUuid(){
    var result = $.ajax({url:"recharge!getSessionUuid.jhtml",async:false});
    var uuid = JSON.parse(result.responseText).info.uuid;
    return uuid;
}
