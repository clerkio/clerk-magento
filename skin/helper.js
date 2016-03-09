// http://stackoverflow.com/questions/6832596/
// how-to-compare-software-version-number-using-js-only-number
function versionCompare(v1, v2, options) {
    var lexicographical = options && options.lexicographical,
        zeroExtend = options && options.zeroExtend,
        v1parts = v1.split('.'),
        v2parts = v2.split('.');

    function isValidPart(x) {
        return (lexicographical ? /^\d+[A-Za-z]*$/ : /^\d+$/).test(x);
    }

    if (!v1parts.every(isValidPart) || !v2parts.every(isValidPart)) {
        return NaN;
    }

    if (zeroExtend) {
        while (v1parts.length < v2parts.length) v1parts.push("0");
        while (v2parts.length < v1parts.length) v2parts.push("0");
    }

    if (!lexicographical) {
        v1parts = v1parts.map(Number);
        v2parts = v2parts.map(Number);
    }

    for (var i = 0; i < v1parts.length; ++i) {
        if (v2parts.length == i) {
            return 1;
        }

        if (v1parts[i] == v2parts[i]) {
            continue;
        }
        else if (v1parts[i] > v2parts[i]) {
            return 1;
        }
        else {
            return -1;
        }
    }

    if (v1parts.length != v2parts.length) {
        return -1;
    }

    return 0;
}

// Use Clerk.iu.$ if jquery version leq than 1.4.3.
// https://help.clerk.io/getting-started/any-platform
// Note form page: This example requires jQuery version 1.4.3 or above. If you
// use an older version then just replace all occurrences of jQuery with
// Clerk.ui.$.
function useClerkjQuery(){
    if (typeof jQuery != 'undefined') {  
		return versionCompare(jQuery.fn.jquery, '1.4.3') !== 1;
	}
	return true
}

function clerk_fire_power_popup() {
    Clerk.ui.popup('#clerk-power-popup').show();
}

function clerk_close_power_popup() {
    Clerk.ui.popup('#clerk-power-popup').close();
}
