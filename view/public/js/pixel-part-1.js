
var $jscomp=$jscomp||{};$jscomp.scope={};$jscomp.ASSUME_ES5=!1;$jscomp.ASSUME_NO_NATIVE_MAP=!1;$jscomp.ASSUME_NO_NATIVE_SET=!1;$jscomp.defineProperty=$jscomp.ASSUME_ES5||"function"==typeof Object.defineProperties?Object.defineProperty:function(a,b,c){a!=Array.prototype&&a!=Object.prototype&&(a[b]=c.value)};$jscomp.getGlobal=function(a){return"undefined"!=typeof window&&window===a?a:"undefined"!=typeof global&&null!=global?global:a};$jscomp.global=$jscomp.getGlobal(this);$jscomp.SYMBOL_PREFIX="jscomp_symbol_";
$jscomp.initSymbol=function(){$jscomp.initSymbol=function(){};$jscomp.global.Symbol||($jscomp.global.Symbol=$jscomp.Symbol)};$jscomp.Symbol=function(){var a=0;return function(b){return $jscomp.SYMBOL_PREFIX+(b||"")+a++}}();
$jscomp.initSymbolIterator=function(){$jscomp.initSymbol();var a=$jscomp.global.Symbol.iterator;a||(a=$jscomp.global.Symbol.iterator=$jscomp.global.Symbol("iterator"));"function"!=typeof Array.prototype[a]&&$jscomp.defineProperty(Array.prototype,a,{configurable:!0,writable:!0,value:function(){return $jscomp.arrayIterator(this)}});$jscomp.initSymbolIterator=function(){}};$jscomp.arrayIterator=function(a){var b=0;return $jscomp.iteratorPrototype(function(){return b<a.length?{done:!1,value:a[b++]}:{done:!0}})};
$jscomp.iteratorPrototype=function(a){$jscomp.initSymbolIterator();a={next:a};a[$jscomp.global.Symbol.iterator]=function(){return this};return a};$jscomp.makeIterator=function(a){$jscomp.initSymbolIterator();var b=a[Symbol.iterator];return b?b.call(a):$jscomp.arrayIterator(a)};
window.wt_webpush=function(a){if("after"===a.type&&("page"===a.mode||"link"===a.mode||"click"===a.mode)){var b=a.instance,c=wt_webpushConfig;a=function(a){a&&"object"===typeof a&&"dmc"===a.action&&(a=a.value,c.useUserMatching&&a&&b.sendinfo({linkId:"webtrekk_ignore",urmCategory:{701:a,713:"1"}}))};if(b.customerId||b.config.customerId)window.mappWebpushMessage=window.mappWebpushMessage||[],window.mappWebpushMessage.push({action:"alias",value:b.customerId||b.config.customerId});for(var e=$jscomp.makeIterator(window.wtWebpushMessage||
    []),d=e.next();!d.done;d=e.next())a(d.value);window.wtWebpushMessage={version:"1.0.1",length:0,push:a}}};window.wts=window.wts||[];window.wts.push(["wt_webpush"]);
