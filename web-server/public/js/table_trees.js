function tree_get_table(tree_type) {
    var tree = []
    if (tree_type === "asns") {
        tree = asns_rank_tree;
    } else if (tree_type === "asns_top_ten") {
        asns["label"]= "cone size";
        tree = asns_rank_top_ten_tree;
    } else if (tree_type === "asn_neighbors") {
        tree = asn_neighbors_tree;
    } else if (tree_type == "asn_search") {
        tree = asn_search_tree;
    } else if (tree_type === "orgs") {
        tree = orgs_rank_tree;
    } else if (tree_type === "org") {
        tree = org_rank_tree;
    } else {
        tree = orgs_membersrank_tree;
    }
    
    // Find out the maxium depth of each node 
    // and width
    height_max = tree_set_width_height(tree);

    // create rowpsan and colspan values
    tree_set_dimentions(height_max, tree);
    return tree;
}

function null_zero_if_ranked(key, obj) {
    if (key in obj) {
        return obj[key];
    } else if ("rank" in obj) {
        return 0;
    }
    return undefined;
}

var rank = {
    "id": "rank",
    "label": "AS Rank",
    "children": undefined,
    "td_class":"center-align",
}

var asn = {
    "id": "id",
    "label": "AS Number",
    "children": undefined,
    "func": function(mode, html_url, obj) { 
        return '<a href="'+html_url+'/asns/'+obj.id+'">'+obj.id+"</a>";
    },
    "td_class":"center-align",
}

var org = {
    "id": "org",
    "label": "Organization",
    "func": function(mode, html_url, obj) {
        var org = id in obj ? obj.org : obj;
        return '<a href="'+html_url+'/orgs/'+org.id+'">'+ null_unknown(org.name)+"</a>";
    },
    "td_class":"left-align asrank-row-org",
}

var country = {
    "id": "country",
    "label": "",
    "children": undefined,
    "func": function (mode, html_url,obj) { 
        if (!("country" in obj)) {
            return "";
        } else {
            return  '<span class="flag-icon flag-icon-'+obj.country.toLowerCase()+'"></span>'; 
        }
    },
    "td_class":"center-align asrank-row-org",
}

var as_name = {
    "id": "name",
    "label": "AS Name",
    "children": undefined,
    "td_class":"left-align asrank-row-org",
}

function cone_access(id,obj) {
    var val = obj.cone[id];
    if ((val === undefined || val === null) && "rank" in obj) {
        val = 0;
    }
    return val;
}

var asns = {
    "id": "asns",
    "label": "ASNs",
    "children": undefined,
    "func": function (mode, html_url, obj) { return cone_access("asns",obj); },
}

var orgs = {
    "id": "orgs",
    "label": "Orgs",
    "children": undefined,
    "func": function (mode, html_url, obj) { return cone_access("orgs",obj); },
}

var prefixes = {
    "id": "prefixes",
    "label": "Prefixes",
    "children": undefined,
    "func": function (mode, html_url, obj) { return cone_access("prefixes",obj); },
}

var addresses = {
    "id": "addresses",
    "label": "Addresses",
    "children": undefined,
    "func": function (mode, html_url, obj) { return cone_access("addresses",obj); },
}

var transit = {
    "id": "transit",
    "label": "Transit<br>ASN Degree",
    "func": function(mode, html_url, obj) {
        var val = null;
        if (mode == "orgs") {
            val = obj.degree.asn.transit
        } else {
            val = obj.degree.transits
        }
        if ((val === undefined || val === null) && "rank" in obj) {
            val = 0;
        }
        return val;
    },
    "td_class":"center-align",
}

var number_asns = {
    "id": "number",
    "label": "Number of",
    "children": [asns, prefixes, addresses],
}

var percentage_asns = {
    "id": "percentage",
    "label": "Percentages of All",
    "children": add_percentage_func([asns, prefixes, addresses]),
}

var org_number_asns = {
    "id": "members",
    "label":"Num. of<br>ASNs",
    "func":function (mode, html_url,obj) {
        if (! "number_members_ranked" in obj) {
            return null_unknown(null);
        }
        return obj["number_members_ranked"]
    },
}

///////////////////////////////////
/// Asn Links columns
///////////////////////////////////

var asn_neighbors_asn = copy_hash(asn);
asn_neighbors_asn["label"] = "AS neighbors";
asn_neighbors_asn["td_class"] = "right-align";

asn_neighbors_asns = copy_hash(asns);
asn_neighbors_asns["label"] = "AS customer cone";

var number_paths = {
    "id": "paths",
    "label": "number of paths",
}

var relationship = {
    "id": "relationship",
    "label": "relationship",
}

///////////////////////////////////
/// Asn Search columns
///////////////////////////////////
var asn_search_asn = {
    "id": "asn",
    "label": "AS number",
    "func": function(mode, html_url, obj) { 
        return '<a href="'+html_url+'/asns/'+obj.asn+'">'+obj.asn+"</a>";
    },
    "td_class":"center-align",
}

var asn_search_name = {
    "id": "name",
    "label": "AS name",
    //"func": function(mode, html_url, obj) { 
        //return '<a href="'+html_url+'/asns/'+obj.asn+'">'+obj.name+"</a>";
    //},
    "td_class":"center-align",
}

var asn_search_org = copy_hash(org);
asn_search_org["td_class"] = "center-align";

///////////////////////////////////
/// Org columns
///////////////////////////////////

var number_orgs = {
    "id": "number",
    "label": "Number of",
    "children": [orgs, asns, prefixes, addresses],
}

var percentage_orgs =  {
    "id": "percentage",
    "label": "Percentages of All",
    "children": add_percentage_func([orgs, asns, prefixes, addresses]),
}

const asns_rank_tree = [
    rank, asn, org, country, 
	{
		"id": "cone",
		"label": "Customer Cone",
		"children": [number_asns, percentage_asns],
    },
    transit,
];

const asns_rank_top_ten_tree = [
    rank, asn, org, country, asns
];


const asn_neighbors_tree = [
    rank, asn_neighbors_asn, org, country,
    asn_neighbors_asns, number_paths, relationship
];

const asn_search_tree = [
    rank, asn_search_asn, asn_search_name, asn_search_org
];

const orgs_rank_tree = [
    rank, org, country, org_number_asns,
	{
		"id": "cone",
		"label": "Customer Cone",
		"children": [number_orgs, percentage_orgs],
    },
    transit,
];

const org_rank_tree = [
    org,
	{
		"id": "cone",
		"label": "Customer Cone",
		"children": [number_asns, percentage_asns],
    },
    transit,
];

const orgs_membersrank_tree = [
	rank, asn, as_name, 
	{
		"id": "cone",
		"label": "Customer Cone",
		"children": [number_asns, percentage_asns],
    },
    transit,
];

////////////////////////////////////////////////////////////////
// Makes a copy of a hash
function copy_hash(object) {
    var copy = {}
    for (key in object) {
        copy[key] = object[key]
    }
    return copy;
}

////////////////////////////////////////////////////////////////
// Makes a copy of the original object
// and set the percentage_bar flag
function add_percentage_func(objects) {
    var copies = []
    for (i in objects) {
        var copy = {}
        copies[i] = copy;
        for (key in objects[i]) {
            copy[key] = objects[i][key]
        }
        copy["percentage_bar"] = true;
    }
    return copies;
}


////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////
// Find the height and width of each tree cel
function tree_set_width_height(tree) {
    var height_max = 0;
    tree.forEach(function(t) {
        if (t.children === undefined){
            t.height = 1;
            t.width = 1;
        } else {
            t.width = 0;
            t.height = 0;
            tree_set_width_height(t.children)
            t.children.forEach(function(c) {
                t.width += c.width
                h = 1 + c.height
                if (h > t.height) {
                    t.height = h;
                }
            });
        }
        if (height_max < t.height) {
            height_max = t.height;
        }
    });
    return height_max;
}

// use the height and width to set the cell's col and row span
function tree_set_dimentions(height_max, tree) {
    tree.forEach(function(t) {
        t.colspan = t.width;
        t.rowspan = height_max-t.height+1;
        if (t.children !== undefined){
            tree_set_dimentions(height_max-1, t.children);
        }
    });
}

