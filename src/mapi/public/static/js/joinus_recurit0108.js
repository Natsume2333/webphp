function showpages(page_num,post_type,page_index){
    	$.ajax({
            type: "get",
            async: false,
            url: '/index!queryRecruitList.jhtml',
            data: {post_type:post_type,pageIndex:page_num,pageIndex:page_index},
            dataType: "jsonp",
            jsonp: "callback",
            jsonpCallback: "totalNumCallback",
            success: function(data) {
                // console.log(data);
                var dataInfo = data.info;
                var page_number = data.page_number;
                if (data.resultCode == 1000) {
                	var div = document.getElementById("itemlct");
                	var post_type_ch = "";
                	div.innerHTML = "";
                	var divPages = document.getElementById("pages");
                	divPages.innerHTML = "";
                	for(var j = 1; j <= page_number; j++){
                       	if(page_index==j){
                       		divPages.innerHTML +="<div class=\"page active\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}else{
                       		divPages.innerHTML +="<div class=\"page\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}
                	}
                	if(post_type=='1'){
                		var div = document.getElementById("itemlct");
                	}
                	if(post_type=='2'){
                		var div = document.getElementById("product");
                	}
                	if(post_type=='3'){
                		var div = document.getElementById("test");
                	}
                	if(post_type=='4'){
                		var div = document.getElementById("big_data");
                	}
                	if(post_type=='5'){
                		var div = document.getElementById("operate");
                	}
                	if(post_type=='99'){
                		var div = document.getElementById("others");
                	}
                	var post_type_ch = "";
                	div.innerHTML = "";
               		for (var i = 0; i < dataInfo.length; i++) {
               			if(dataInfo[i].post_type=='1'){
               				post_type_ch = '技术类';
               			}
               			if(dataInfo[i].post_type=='2'){
               				post_type_ch = '产品类';
               			}
               			if(dataInfo[i].post_type=='3'){
               				post_type_ch = '测试类';
               			}
               			if(dataInfo[i].post_type=='4'){
               				post_type_ch = '运营类';
               			}
               			if(dataInfo[i].post_type=='5'){
               				post_type_ch = '大数据类';
               			}
               			if(dataInfo[i].post_type=='99'){
               				post_type_ch = '其他类';
               			}
               			div.innerHTML += "<div class=\"topitems\" data-type=\"1\"><div class=\"xiao1\"><span class=\"zwmz\">"
               			+dataInfo[i].post_name+"</span><img src=\""+dataInfo[i].img1+"\" class=\"imgji\"><img src=\""+dataInfo[i].img2+"\" class=\"imgji\"></div><div class=\"xiao2\">"+post_type_ch+"</div><div class=\"xiao3\">杭州<span class=\"tubiao\">"
               			+"</span></div></div><div class=\"bottomitem\"><div class=\"shang\"><div class=\"gwzz m10\"><pre>"+dataInfo[i].post_duty
               			+"</pre></div></div><div class=\"xia\"><div class=\"zwyq m10\"><pre>"+dataInfo[i].post_requirement+"</pre></div></div>";
               		}
                    divPages.appendChild(divPages);
               		div.appendChild(div);
                }
            },
            error: function(error) {
                console.log('fail');
            }
        });
	}

    function showpages2(page_num,post_type,page_index){
    	$.ajax({
            type: "get",
            async: false,
            url: '/index!queryRecruitList.jhtml',
            data: {post_type:post_type,pageIndex:page_num,pageIndex:page_index},
            dataType: "jsonp",
            jsonp: "callback",
            jsonpCallback: "totalNumCallback",
            success: function(data) {
                // console.log(data);
                var dataInfo = data.info;
                var page_number = data.page_number;
                if (data.resultCode == 1000) {
                	var div = document.getElementById("itemlct");
                	var post_type_ch = "";
                	div.innerHTML = "";
                	var divPages = document.getElementById("pages");
                	divPages.innerHTML = "";
                	for(var j = 1; j <= page_number; j++){
                       	if(page_index==j){
                       		divPages.innerHTML +="<div class=\"page active\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}else{
                       		divPages.innerHTML +="<div class=\"page\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}
                	}
                	if(post_type=='1'){
                		var div = document.getElementById("itemlct");
                	}
                	if(post_type=='2'){
                		var div = document.getElementById("product");
                	}
                	if(post_type=='3'){
                		var div = document.getElementById("test");
                	}
                	if(post_type=='4'){
                		var div = document.getElementById("big_data");
                	}
                	if(post_type=='5'){
                		var div = document.getElementById("operate");
                	}
                	if(post_type=='99'){
                		var div = document.getElementById("others");
                	}
                	var post_type_ch = "";
                	div.innerHTML = "";
               		for (var i = 0; i < dataInfo.length; i++) {
               			if(dataInfo[i].post_type=='1'){
               				post_type_ch = '技术类';
               			}
               			if(dataInfo[i].post_type=='2'){
               				post_type_ch = '产品类';
               			}
               			if(dataInfo[i].post_type=='3'){
               				post_type_ch = '测试类';
               			}
               			if(dataInfo[i].post_type=='4'){
               				post_type_ch = '运营类';
               			}
               			if(dataInfo[i].post_type=='5'){
               				post_type_ch = '大数据类';
               			}
               			if(dataInfo[i].post_type=='99'){
               				post_type_ch = '其他类';
               			}
               			div.innerHTML += "<div class=\"topitems\" data-type=\"1\"><div class=\"xiao1\"><span class=\"zwmz\">"
               			+dataInfo[i].post_name+"</span><img src=\""+dataInfo[i].img1+"\" class=\"imgji\"><img src=\""+dataInfo[i].img2+"\" class=\"imgji\"></div><div class=\"xiao2\">"+post_type_ch+"</div><div class=\"xiao3\">杭州<span class=\"tubiao\">"
               			+"</span></div></div><div class=\"bottomitem\"><div class=\"shang\"><div class=\"gwzz m10\"><pre>"+dataInfo[i].post_duty
               			+"</pre></div></div><div class=\"xia\"><div class=\"zwyq m10\"><pre>"+dataInfo[i].post_requirement+"</pre></div></div>";
               		}
                    divPages.appendChild(divPages);
               		div.appendChild(div);
                }
            },
            error: function(error) {
                console.log('fail');
            }
        });
	}
    
    $(function() {
    	var post_type = 1;
        /**/
        //招聘信息列表
        $.ajax({
            type: "get",
            async: false,
            url: '/index!queryRecruitList.jhtml',
            data: {post_type:post_type},
            dataType: "jsonp",
            jsonp: "callback",
            jsonpCallback: "totalNumCallback",
            success: function(data) {
                // console.log(data);
                var dataInfo = data.info;
                var post_type_i = data.post_type_i;
                var page_number = data.page_number;
                var page_index = data.page_index;
                if (data.resultCode == 1000) {
                	var div = document.getElementById("itemlct");
                	var post_type_ch = "";
                	div.innerHTML = "";
                	var divPages = document.getElementById("pages");
                	divPages.innerHTML = "";
                	for(var j = 1; j <= page_number; j++){
                       	if(page_index==j){
                       		divPages.innerHTML +="<div class=\"page active\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}else{
                       		divPages.innerHTML +="<div class=\"page\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}
                	}
               		for (var i = 0; i < dataInfo.length; i++) {
               			if(dataInfo[i].post_type=='1'){
               				post_type_ch = '技术类';
               			}
               			if(dataInfo[i].post_type=='2'){
               				post_type_ch = '产品类';
               			}
               			if(dataInfo[i].post_type=='3'){
               				post_type_ch = '测试类';
               			}
               			if(dataInfo[i].post_type=='4'){
               				post_type_ch = '运营类';
               			}
               			if(dataInfo[i].post_type=='5'){
               				post_type_ch = '大数据类';
               			}
               			if(dataInfo[i].post_type=='99'){
               				post_type_ch = '其他类';
               			}
               			div.innerHTML += "<div class=\"topitems\" data-type=\"1\"><div class=\"xiao1\"><span class=\"zwmz\">"
               			+dataInfo[i].post_name+"</span><img src=\""+dataInfo[i].img1+"\""
               			+" class=\"imgji\"><img src=\""+dataInfo[i].img2+"\" class=\"imgji\"></div><div class=\"xiao2\">"+post_type_ch+"</div><div class=\"xiao3\">杭州<span class=\"tubiao\">"
               			+"</span></div></div><div class=\"bottomitem\"><div class=\"shang\"><div class=\"gwzz m10\"><pre>"+dataInfo[i].post_duty
               			+"</pre></div></div><div class=\"xia\"><div class=\"zwyq m10\"><pre>"+dataInfo[i].post_requirement+"</pre></div></div>";
//                			alert(dataInfo[i].post_id);
//                			alert(dataInfo[i].post_name);
//                			alert(dataInfo[i].post_type);
//                			alert(dataInfo[i].post_duty);
//                			alert(dataInfo[i].post_requirement);
               		}
               		div.appendChild(div);
               		divPages.appendChild(divPages);
                }
            },
            error: function(error) {
                console.log('fail');
            }
        });
    });
    function changes(post_type){
    	var divPages = document.getElementById("pages");
    	divPages.innerHTML = "";
    	$.ajax({
            type: "get",
            async: false,
            url: '/index!queryRecruitList.jhtml',
            data: {post_type:post_type},
            dataType: "jsonp",
            jsonp: "callback",
            jsonpCallback: "totalNumCallback",
            success: function(data) {
                // console.log(data);
                var dataInfo = data.info;
                var post_type_i = data.post_type_i;
                var page_number = data.page_number;
                var page_index = data.page_index;
                if (data.resultCode == 1000) {
                	var div = document.getElementById("itemlct");
                	var post_type_ch = "";
                	div.innerHTML = "";
                	var divPages = document.getElementById("pages");
                	divPages.innerHTML = "";
                	for(var j = 1; j <= page_number; j++){
                       	if(page_index==j){
                       		divPages.innerHTML +="<div class=\"page active\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}else{
                       		divPages.innerHTML +="<div class=\"page\" onclick=\"showpages("+page_number+","+post_type+","+j+")\">"+j+"</div>";
                       	}
                	}
                	if(post_type=='1'){
                		var div = document.getElementById("itemlct");
                	}
                	if(post_type=='2'){
                		var div = document.getElementById("product");
                	}
                	if(post_type=='3'){
                		var div = document.getElementById("test");
                	}
                	if(post_type=='4'){
                		var div = document.getElementById("big_data");
                	}
                	if(post_type=='5'){
                		var div = document.getElementById("operate");
                	}
                	if(post_type=='99'){
                		var div = document.getElementById("others");
                	}
                	var post_type_ch = "";
                	div.innerHTML = "";
               		for (var i = 0; i < dataInfo.length; i++) {
               			if(dataInfo[i].post_type=='1'){
               				post_type_ch = '技术类';
               			}
               			if(dataInfo[i].post_type=='2'){
               				post_type_ch = '产品类';
               			}
               			if(dataInfo[i].post_type=='3'){
               				post_type_ch = '测试类';
               			}
               			if(dataInfo[i].post_type=='4'){
               				post_type_ch = '运营类';
               			}
               			if(dataInfo[i].post_type=='5'){
               				post_type_ch = '大数据类';
               			}
               			if(dataInfo[i].post_type=='99'){
               				post_type_ch = '其他类';
               			}
               			div.innerHTML += "<div class=\"topitems\" data-type=\"1\"><div class=\"xiao1\"><span class=\"zwmz\">"
               			+dataInfo[i].post_name+"</span><img src=\""+dataInfo[i].img1+"\" class=\"imgji\"><img src=\""+dataInfo[i].img2+"\" class=\"imgji\"></div><div class=\"xiao2\">"+post_type_ch+"</div><div class=\"xiao3\">杭州<span class=\"tubiao\">"
               			+"</span></div></div><div class=\"bottomitem\"><div class=\"shang\"><div class=\"gwzz m10\"><pre>"+dataInfo[i].post_duty
               			+"</pre></div></div><div class=\"xia\"><div class=\"zwyq m10\"><pre>"+dataInfo[i].post_requirement+"</pre></div></div>";
               		}
                    divPages.appendChild(divPages);
               		div.appendChild(div);
                }
            },
            error: function(error) {
                console.log('fail');
            }
        });
    }