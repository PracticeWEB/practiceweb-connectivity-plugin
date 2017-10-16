jQuery(document).ready(function( $ ) {


// get containers
var container = jQuery('.deadlines-container');
var list = jQuery('.deadlines-list-container', container);

var bindFilter = function() {
    jQuery('.deadlines-apply-filter', container).on('click', function () {
        // Get current path.
        var url = window.location.pathname;
        // Get args.
        var search = window.location.search;
        // Jquery Doesn't have a reverse for param.
        var queryArgs = paramsToObj(search);
        // Add input.
        queryArgs["dateRange"] = jQuery('select[name=dateRange]', container).val();
        queryArgs["taxonomy"] = jQuery('input[name=taxonomy]:checked', container).map(function(){return this.value}).get();
        // Rebuild the url.
        var params = jQuery.param(queryArgs);
        var fullUrl = url + '?' + params;
        // Refetch and replace the list.
        list.load(fullUrl + ' .deadlines-list');
    });
}
bindFilter();


// Refactor arg to string
var paramsToObj = function(string) {
    string = string.replace(new RegExp("^[\\?]"), "");
    var params = new Object();
    if (string) {
        var parts = string.split('&');
        console.log(parts);

        for (i in parts) {
            var part = parts[i];
            var param = part.split('=', 2);
            var key = param[0];
            var value = param[1];
            // Is it an array.
            if (key.search("\\[\\]") !== -1) {
                // Strip [] for the key.
                key = key.replace(new RegExp("\\[\\]$"), "");
                // Check if we need to initialize.
                if (typeof params[key] === 'undefined') {
                    params[key] = [value];
                } else {
                    params[key].push(value);
                }
            }
            else {
                params[key] = value;
            }
        }
    }
    return params;
}
});