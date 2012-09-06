function popWin(a,b,c){var b=window.open(a,b,c);b.focus()}function setLocation(a){window.location.href=a}function setPLocation(a,b){if(b){window.opener.focus()}window.opener.location.href=a}function setLanguageCode(a,b){var c=window.location.href;var d="",e;if(e=c.match(/\#(.*)$/)){c=c.replace(/\#(.*)$/,"");d=e[0]}if(c.match(/[?]/)){var f=/([?&]store=)[a-z0-9_]*/;if(c.match(f)){c=c.replace(f,"$1"+a)}else{c+="&store="+a}var f=/([?&]from_store=)[a-z0-9_]*/;if(c.match(f)){c=c.replace(f,"")}}else{c+="?store="+a}if(typeof b!="undefined"){c+="&from_store="+b}c+=d;setLocation(c)}function decorateGeneric(a,b){var c=["odd","even","first","last"];var d={};var e=a.length;if(e){if(typeof b=="undefined"){b=c}if(!b.length){return}for(var f in c){d[c[f]]=false}for(var f in b){d[b[f]]=true}if(d.first){Element.addClassName(a[0],"first")}if(d.last){Element.addClassName(a[e-1],"last")}for(var g=0;g<e;g++){if((g+1)%2==0){if(d.even){Element.addClassName(a[g],"even")}}else{if(d.odd){Element.addClassName(a[g],"odd")}}}}}function decorateTable(a,b){var a=$(a);if(a){var c={tbody:false,"tbody tr":["odd","even","first","last"],"thead tr":["first","last"],"tfoot tr":["first","last"],"tr td":["last"]};if(typeof b!="undefined"){for(var d in b){c[d]=b[d]}}if(c["tbody"]){decorateGeneric(a.select("tbody"),c["tbody"])}if(c["tbody tr"]){decorateGeneric(a.select("tbody tr"),c["tbody tr"])}if(c["thead tr"]){decorateGeneric(a.select("thead tr"),c["thead tr"])}if(c["tfoot tr"]){decorateGeneric(a.select("tfoot tr"),c["tfoot tr"])}if(c["tr td"]){var e=a.select("tr");if(e.length){for(var f=0;f<e.length;f++){decorateGeneric(e[f].getElementsByTagName("TD"),c["tr td"])}}}}}function decorateList(a,b){if($(a)){if(typeof b=="undefined"){var c=$(a).select("li")}else{var c=$(a).childElements()}decorateGeneric(c,["odd","even","last"])}}function decorateDataList(a){a=$(a);if(a){decorateGeneric(a.select("dt"),["odd","even","last"]);decorateGeneric(a.select("dd"),["odd","even","last"])}}function parseSidUrl(a,b){var c=a.indexOf("/?SID=");var d="";b=b!=undefined?b:"";if(c>-1){d="?"+a.substring(c+2);a=a.substring(0,c+1)}return a+b+d}function formatCurrency(a,b,c){var d=isNaN(b.precision=Math.abs(b.precision))?2:b.precision;var e=isNaN(b.requiredPrecision=Math.abs(b.requiredPrecision))?2:b.requiredPrecision;d=e;var f=isNaN(b.integerRequired=Math.abs(b.integerRequired))?1:b.integerRequired;var g=b.decimalSymbol==undefined?",":b.decimalSymbol;var h=b.groupSymbol==undefined?".":b.groupSymbol;var i=b.groupLength==undefined?3:b.groupLength;var k="";if(c==undefined||c==true){k=a<0?"-":c?"+":""}else if(c==false){k=""}var l=parseInt(a=Math.abs(+a||0).toFixed(d))+"";var m=l.length<f?f-l.length:0;while(m){l="0"+l;m--}j=(j=l.length)>i?j%i:0;re=new RegExp("(\\d{"+i+"})(?=\\d)","g");var n=(j?l.substr(0,j)+h:"")+l.substr(j).replace(re,"$1"+h)+(d?g+Math.abs(a-l).toFixed(d).replace(/-/,0).slice(2):"");var o="";if(b.pattern.indexOf("{sign}")==-1){o=k+b.pattern}else{o=b.pattern.replace("{sign}",k)}return o.replace("%s",n).replace(/^\s\s*/,"").replace(/\s\s*$/,"")}function expandDetails(a,b){if(Element.hasClassName(a,"show-details")){$$(b).each(function(a){a.hide()});Element.removeClassName(a,"show-details")}else{$$(b).each(function(a){a.show()});Element.addClassName(a,"show-details")}}function truncateOptions(){$$(".truncated").each(function(a){Event.observe(a,"mouseover",function(){if(a.down("div.truncated_full_value")){a.down("div.truncated_full_value").addClassName("show")}});Event.observe(a,"mouseout",function(){if(a.down("div.truncated_full_value")){a.down("div.truncated_full_value").removeClassName("show")}})})}function fireEvent(a,b){if(document.createEventObject){var c=document.createEventObject();return a.fireEvent("on"+b,c)}else{var c=document.createEvent("HTMLEvents");c.initEvent(b,true,true);return!a.dispatchEvent(c)}}var isIE=navigator.appVersion.match(/MSIE/)=="MSIE";if(!window.Varien)var Varien=new Object;Varien.showLoading=function(){Element.show("loading-process")};Varien.hideLoading=function(){Element.hide("loading-process")};Varien.GlobalHandlers={onCreate:function(){Varien.showLoading()},onComplete:function(){if(Ajax.activeRequestCount==0){Varien.hideLoading()}}};Ajax.Responders.register(Varien.GlobalHandlers);Varien.searchForm=Class.create();Varien.searchForm.prototype={initialize:function(a,b,c){this.form=$(a);this.field=$(b);this.emptyText=c;Event.observe(this.form,"submit",this.submit.bind(this));Event.observe(this.field,"focus",this.focus.bind(this));Event.observe(this.field,"blur",this.blur.bind(this));this.blur()},submit:function(a){if(this.field.value==this.emptyText||this.field.value==""){Event.stop(a);return false}return true},focus:function(a){if(this.field.value==this.emptyText){this.field.value=""}},blur:function(a){if(this.field.value==""){this.field.value=this.emptyText}},initAutocomplete:function(a,b){new Ajax.Autocompleter(this.field,b,a,{paramName:this.field.name,method:"get",minChars:2,updateElement:this._selectAutocompleteItem.bind(this),onShow:function(a,b){if(!b.style.position||b.style.position=="absolute"){b.style.position="absolute";Position.clone(a,b,{setHeight:false,offsetTop:a.offsetHeight})}Effect.Appear(b,{duration:0})}})},_selectAutocompleteItem:function(a){if(a.title){this.field.value=a.title}this.form.submit()}};Varien.Tabs=Class.create();Varien.Tabs.prototype={initialize:function(a){var b=this;$$(a+" a").each(this.initTab.bind(this))},initTab:function(a){a.href="javascript:void(0)";if($(a.parentNode).hasClassName("active")){this.showContent(a)}a.observe("click",this.showContent.bind(this,a))},showContent:function(a){var b=$(a.parentNode),c=$(b.parentNode);c.getElementsBySelector("li","ol").each(function(a){var c=$(a.id+"_contents");if(a==b){a.addClassName("active");c.show()}else{a.removeClassName("active");c.hide()}})}};Varien.DateElement=Class.create();Varien.DateElement.prototype={initialize:function(a,b,c,d){if(a=="id"){this.day=$(b+"day");this.month=$(b+"month");this.year=$(b+"year");this.full=$(b+"full");this.advice=$(b+"date-advice")}else if(a=="container"){this.day=b.day;this.month=b.month;this.year=b.year;this.full=b.full;this.advice=b.advice}else{return}this.required=c;this.format=d;this.day.addClassName("validate-custom");this.day.validate=this.validate.bind(this);this.month.addClassName("validate-custom");this.month.validate=this.validate.bind(this);this.year.addClassName("validate-custom");this.year.validate=this.validate.bind(this);this.setDateRange(false,false);this.year.setAttribute("autocomplete","off");this.advice.hide()},validate:function(){var a=false,b=parseInt(this.day.value.replace(/^0*/,""))||0,c=parseInt(this.month.value.replace(/^0*/,""))||0,d=parseInt(this.year.value)||0;if(!b&&!c&&!d){if(this.required){a="This date is a required value."}else{this.full.value=""}}else if(!b||!c||!d){a="Please enter a valid full date."}else{var e=new Date,f=0,g=null;e.setYear(d);e.setMonth(c-1);e.setDate(32);f=32-e.getDate();if(!f||f>31)f=31;if(b<1||b>f){g="day";a="Please enter a valid day (1-%d)."}else if(c<1||c>12){g="month";a="Please enter a valid month (1-12)."}else{if(b%10==b)this.day.value="0"+b;if(c%10==c)this.month.value="0"+c;this.full.value=this.format.replace(/%[mb]/i,this.month.value).replace(/%[de]/i,this.day.value).replace(/%y/i,this.year.value);var h=this.month.value+"/"+this.day.value+"/"+this.year.value;var i=new Date(h);if(isNaN(i)){a="Please enter a valid date."}else{this.setFullDate(i)}}var j=false;if(!a&&!this.validateData()){g=this.validateDataErrorType;j=this.validateDataErrorText;a=j}}if(a!==false){try{a=Translator.translate(a)}catch(k){}if(!j){this.advice.innerHTML=a.replace("%d",f)}else{this.advice.innerHTML=this.errorTextModifier(a)}this.advice.show();return false}this.day.removeClassName("validation-failed");this.month.removeClassName("validation-failed");this.year.removeClassName("validation-failed");this.advice.hide();return true},validateData:function(){var a=this.fullDate.getFullYear();var b=new Date;this.curyear=b.getFullYear();return a>=1900&&a<=this.curyear},validateDataErrorType:"year",validateDataErrorText:"Please enter a valid year (1900-%d).",errorTextModifier:function(a){return a.replace("%d",this.curyear)},setDateRange:function(a,b){this.minDate=a;this.maxDate=b},setFullDate:function(a){this.fullDate=a}};Varien.DOB=Class.create();Varien.DOB.prototype={initialize:function(a,b,c){var d=$$(a)[0];var e={};e.day=Element.select(d,".dob-day input")[0];e.month=Element.select(d,".dob-month input")[0];e.year=Element.select(d,".dob-year input")[0];e.full=Element.select(d,".dob-full input")[0];e.advice=Element.select(d,".validation-advice")[0];new Varien.DateElement("container",e,b,c)}};Varien.dateRangeDate=Class.create();Varien.dateRangeDate.prototype=Object.extend(new Varien.DateElement,{validateData:function(){var a=true;if(this.minDate||this.maxValue){if(this.minDate){this.minDate=new Date(this.minDate);this.minDate.setHours(0);if(isNaN(this.minDate)){this.minDate=new Date("1/1/1900")}a=a&&this.fullDate>=this.minDate}if(this.maxDate){this.maxDate=new Date(this.maxDate);this.minDate.setHours(0);if(isNaN(this.maxDate)){this.maxDate=new Date}a=a&&this.fullDate<=this.maxDate}if(this.maxDate&&this.minDate){this.validateDataErrorText="Please enter a valid date between %s and %s"}else if(this.maxDate){this.validateDataErrorText="Please enter a valid date less than or equal to %s"}else if(this.minDate){this.validateDataErrorText="Please enter a valid date equal to or greater than %s"}else{this.validateDataErrorText=""}}return a},validateDataErrorText:"Date should be between %s and %s",errorTextModifier:function(a){if(this.minDate){a=a.sub("%s",this.dateFormat(this.minDate))}if(this.maxDate){a=a.sub("%s",this.dateFormat(this.maxDate))}return a},dateFormat:function(a){return a.getMonth()+1+"/"+a.getDate()+"/"+a.getFullYear()}});Varien.FileElement=Class.create();Varien.FileElement.prototype={initialize:function(a){this.fileElement=$(a);this.hiddenElement=$(a+"_value");this.fileElement.observe("change",this.selectFile.bind(this))},selectFile:function(a){this.hiddenElement.value=this.fileElement.getValue()}};Validation.addAllThese([["validate-custom"," ",function(a,b){return b.validate()}]]);Event.observe(window,"load",function(){truncateOptions()});Element.addMethods({getInnerText:function(a){a=$(a);if(a.innerText&&!Prototype.Browser.Opera){return a.innerText}return a.innerHTML.stripScripts().unescapeHTML().replace(/[\n\r\s]+/g," ").strip()}});if(typeof Range!="undefined"&&!Range.prototype.createContextualFragment){Range.prototype.createContextualFragment=function(a){var b=document.createDocumentFragment(),c=document.createElement("div");b.appendChild(c);c.outerHTML=a;return b}}