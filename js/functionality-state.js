// window.onload = function(){
//   document.getElementById("group_enumeration_toggle_button").onclick = function () {
//
//   };
// };

jQuery(document).ready(function(){
  jQuery(".hides-next-element").next().hide();

      jQuery(".hides-next-element").click(function () {
        jQuery(this).next().slideToggle();
        jQuery(this).children(0).toggleClass("rotate-90-deg-right");
      })
});

function writeLog() {
  console.log("Hellooo");
}