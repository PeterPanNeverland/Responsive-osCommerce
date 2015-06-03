//famcat Family products form functions moved out to include file & changed for use in multiple forms
function updateProductsFamily() {
  updateFamily("new_product");
}
function updateCategoriesFamily() {
  updateFamily("new_category");
}
function updateFamily(formname) {
  var selected_value = document.forms[formname].products_family_select.selectedIndex;
  var famValue = document.forms[formname].products_family_select[selected_value].value;
  document.forms[formname].products_family.value = famValue;
  document.forms[formname].products_family_select.selectedIndex = 0;
}
