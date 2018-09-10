//
// Fills in the footer-dataset information
var dataset_promise = fetch_asrank(RESTFUL_DATABASE_URL+"/ds")
    .then(response => response.data[0]);

function dataset_information() {
    var div = $('#footer-dataset');
    dataset_promise.then(dataset => {
        // populates date/time, ip protocol inside footer
        var date = dataset.date.replace(/(....)(..)(..)/,"$1/$2/$3")
        div.append(date+"<br>")
        var num = '';
        if (dataset.address_family == "AF_INET") {
            num = '4';
        } else if (dataset.address_family == "AF_INET6") {
            num = '6';
        } else {
            num = '?';
        }

        div.append("AS Rank IPv" + num);
        document.getElementById('footer-dataset').setAttribute('title', "The approximate date around which the AS relationship data was inferred from IPv" + num + " BGP AS paths.");
        document.getElementById('footer-dataset').setAttribute('data-original-title', "The approximate date around which the AS relationship data was inferred from IPv" + num + " BGP AS paths.");

        div.ready(function(){
            $('[rel="tooltip"]').tooltip();
        });

        // dataset - populate data sources inside dvi
        var sources_div = $('.asrank-data-sources-set');

        if (sources_div) {
            dataset.sources.forEach( source => {
                source.date = source.date.length == 8 ?
                    source.date.replace(/(....)(..)(..)/,"$1/$2/$3") :
                    source.date.replace(/(....)(..)/,"$1/$2");
                sources_div.append("<span><a href=\"" + source.url + "\">" + source.name
                    + "</a> " + source.date + "</span><span class=\"asrank-data-sources-spacer\">|</span>");
            });

            sources_div.children().last().remove();
        }

        var dataset_div = $('#dataset_table');
        if (dataset_div) {
            table = $('<table class="asrank-info-table table-condensed" border="0">');
            dataset_div.append(table)

            var label_values = [
                ["date", date],
                ["number of ASNs",dataset['asns']],
                ["number of organizations",dataset['orgs']]
            ];
            label_values.forEach( function (label_value) {
                table.append($('<tr><th>'+label_value[0]+'</th><td>'+label_value[1]+'</td></tr>'));
            });
        }
        return dataset;
    });
}

//////////////////////////////////////////////////////////////
// Updated the Citation's start and end
//////////////////////////////////////////////////////////////
function citation_add_date() {
    dataset_promise.then(dataset => {
        month_re = /(....)(..)/;
        $('#citation_month').text(month_re.exec(dataset.date)[2]);
        $('#citation_year').text(month_re.exec(dataset.date)[1]);
    });
}

//////////////////////////////////////////////////////////////
// Table functions
//////////////////////////////////////////////////////////////

function build_info_table(mode, id, page_number, page_count, sort_type, sort_dir, org_info) {
    const tree_headers = tree_get_table(mode);
    const leaves = tree_create_leaves(tree_headers);
    if (mode === "org_members") org_info = JSON.parse(org_info.replace(/&quot;/g, '"'));
    
    //current_url = current_url +=  (mode === "asns") ? "" : (mode ==="orgs") ? "/orgs": ("/orgs/" + id);

    var current_url = host_url() + "/";
    var database_url = RESTFUL_DATABASE_URL +"/";
    if (mode === "org_members") {
        current_url += "orgs/"+id;
        database_url += "orgs/"+id+"/members";
    } else if (mode === "orgs") {
        current_url += "orgs";
        database_url += "orgs";

    } else if (mode === "asn_neighbors") {
        current_url += "asns/" + id;
        database_url += "asns/"+id+"/links";
    } else if (mode === "asn_search") {
        current_url += "asns?name="+id+"&type=search";
        database_url += "asns/by-name/"+id;
    } else { 
        database_url += "asns/ranked";
        if (mode == "asns_top_ten") {
            mode = "asns";
        } else if (mode !== "asns") {
            current_url += "asns";
            console.log("ERROR: unknown mode:"+mode);
        }
    }

    database_url = add_param(database_url, "populate=1");

    if (page_count != null && toString(page_number).length > 0) {
        database_url = add_param(database_url, "page="+page_number);
        current_url = add_param(current_url, "page="+page_number);
    }
    if (page_count != null && toString(page_count).length > 0) {
        database_url = add_param(database_url, "count="+page_count);
        current_url = add_param(current_url, "count="+page_count);
    }


    if (sort_type != null && sort_type.length > 0) {
        database_url = add_param(database_url, "sort="+sort_type);
        current_url = add_param(current_url, "sort_type="+sort_type);
    }

    // Added sort direction...
    if ( sort_dir != null && sort_dir.length > 0 ) {
        database_url = add_param ( database_url, "dir=" + sort_dir );
        current_url = add_param ( current_url, "sort_dir=" + sort_dir );
    }

    var div = $("#"+mode+"_table");
    var table = $('<table class="asrank-asns-table table table-responsive table-bordered table-hover d-table table-striped"></table>')

    get_json(RESTFUL_DATABASE_URL+"/ds", div, function (result) {
        var family = "IPv4";
        var dataset = null;

        if (result.data != null) {
            dataset = result.data[0];
            if (dataset.family == "AF_INET6") family = "IPv6";
        }

        if (page_number < 1) page_number = 1;
        var page_links_header = $('<div></div>');
        div.append(page_links_header);

				// Following test doesn't work properly...at least on my MacOS X PHP 7.1.8
        //if (sort_type == null) {
        if ( ! sort_type )
            sort_type = "rank";

        if ( ! sort_dir )
            sort_dir = "asc";

        // Currently only asns supports sort :(  I don't think this is true
				// anymore?
        //if (mode === "asns") {
            //var sorter = build_sorter(current_url, sort_type, "rank");
            //page_links_header.append(sorter);
        //}


        get_json(database_url, div, function(items) {
            
            var item_arr = items.data;

            total = item_arr.length;
            if ("total" in items) 
                total = items.total;

            page_links_add(page_links_header, current_url, page_number, page_count, total);
            tree_create_header_html ( current_url, table, tree_headers, sort_type, sort_dir );

            div.append(table);
        
            if (mode === "org_members") { 
                const org_tree = tree_get_table("org");
                const org_leaves = tree_create_leaves(org_tree);
                org_leaves[0].colspan = 3;

                var row = $('<tr></tr>');
                table.append(row);
                create_table_html(row, org_leaves, org_info, "orgs", dataset);
            }

            if (item_arr.length == 0) {
                table.append('<tr><td class="asrank-row-none" colspan="'+leaves.length+'">none found</td></tr>');
            } else {
                    
                for (var i = 0; i < item_arr.length; i++) {
                    var row = $('<tr></tr>');
                    table.append(row);
                    create_table_html(row, leaves, item_arr[i], mode, dataset);
                }
            }
            page_links_add(div, current_url, page_number, page_count, total);

        });
    });
}

// create html for percentage styling
function create_percentage_html(leaf, item, mode, dataset, style="") {

    var val = item[leaf[id]];
    if ("func" in leaf) {
        val = leaf["func"](mode,host_url(),item);
    }
    den = dataset[id];

    var content = ""
    if (val == null || val == undefined || den == null || den == undefined) {
        content = null_unknown(null);
    } else {

        var fraction = (100*val)/den;
        var result = Math.round(fraction*100)/100;

        content = "<span class='perc'>"+result+"%</span><div class='perc' style='width:"+fraction+"%'>&nbsp;</div>";
    }
    //return "<td class='d-none d-md-table-cell perc'" + style +">"+content+"</td>\n";
    return "<td class='perc'" + style +">"+content+"</td>\n";
}

// create table body using leaves from table_tree object
function create_table_html(row, leaves, item, mode, dataset) {
    if ("data" in item) item = item.data;
    var html_url = host_url();

    for ( i in leaves ) {
        var td_class = "";
        id = leaves[i]["id"];
        if ("percentage_bar" in leaves[i]) {
            var fraction, result = 0;
            var percentage_html = "<td>"+null_unknown(null)+"</td>";
            if (dataset != null && id in dataset) {
            //if (dataset != null && id in item.cone) {
                //percentage_html = create_percentage_html(item.cone[id], dataset[id])
                percentage_html = create_percentage_html(leaves[i], item, mode, dataset)
            }
            row.append(percentage_html);
        } else {
            var val = null
            var func = leaves[i]["func"]
            if (null != func) {
                val = func(mode,html_url,item);
            } else {
                val = item[id];
            }

            if ("td_class" in leaves[i]) {
                td_class = leaves[i]["td_class"];
            }

            var colspan = ""
            if ("colspan" in leaves[i]) {
                colspan = 'colspan="'+leaves[i].colspan+'"';
            }

            row.append('<td class="' + td_class +'" '+colspan+'>'+null_unknown(val)+'</td>');
            td_class = "";
        }
    }
}

// create table header using table_tree object
function tree_create_header_html(current_url, table, tree_headers, sort_type, sort_dir, depth = 0, rows = [] ) {
    var row = null;
    if (depth >= rows.length) {
        rows[depth] = row = $('<tr></tr>') 
        table.append(row);
    } else {
        row = rows[depth];
    }
    var dimensions = [];
    var sort_types = { "rank":"rank", "asns":"customer_cone_asns", "prefixes":"customer_cone_prefixes", "addresses":"customer_cone_addresses", "transit":"transit_degree" };

    tree_headers.forEach(function(i, key) {
        var obj = tree_headers[key];
        var sort_html = "";

        // If sortable column header, add sort icon...
        if ( obj.id in sort_types && obj.colspan == 1 && typeof obj.percentage_bar == 'undefined' ) {
            var url = update_param(current_url, "sort_type",sort_types[obj.id]);

            var sort_symbol = '&#8711;';
            if ( sort_types[obj.id] == sort_type ) {
                if ( sort_dir == "desc" ) {
                    url = update_param ( url, "sort_dir", "asc" );
                    if ( sort_type == "rank" )
                        sort_symbol = '&#9660;';
                    else
                        sort_symbol = '&#9650;';
                } else {
                    url = update_param ( url, "sort_dir", "desc" );
                    if ( sort_type == "rank" && sort_dir != "desc" )
                        sort_symbol = '&#9650;';
                    else
                        sort_symbol = '&#9660;';
                }
            } else if ( sort_types[obj.id] == "rank" ) {
                sort_symbol = '&#916;';
            }
            sort_html = '&nbsp;<a href="'+url+'"><span style="font-weight:normal;font-size:80%">'+sort_symbol+'&nbsp;</span></a>';
        }
        var th = $('<th>'+obj.label+sort_html+'</th>');

        row.append(th)
        th.attr("colspan",obj.colspan)
        th.attr("rowspan", obj.rowspan);
        if (obj.colspan === 3 && obj.rowspan === 1) th.addClass("d-sm-table-cell");

        if (obj.children !== undefined){
            dim = tree_create_header_html(current_url, table, obj.children, sort_type, sort_dir, depth+1, rows );
        }
    });
}

function tree_create_leaves(tree_headers, leaves = []) {
    var dimensions = [];
    tree_headers.forEach(function(i, key) {
        var obj = tree_headers[key];
        if (obj.children === undefined){
            leaves.push(obj);
        } else {
            tree_create_leaves(obj.children, leaves);
        }
    });

    return leaves;
}

//////////////////////////////////////////////////////////////
// Helper Functions
//////////////////////////////////////////////////////////////
function host_url() {
    var url = location.protocol+"//"+location.hostname;
    if (location.port.length > 0) {
        url += ":"+location.port;
    }
    return url;
}

async function fetch_asrank(url, div) {
    return fetch(url)
    .then(response => {
        if (!response.ok) { throw response }
        return response.json()
    })
    .then(response => {
        if (response == null || typeof(response) != "object" || (response instanceof Array) || (response instanceof Date) || !("data" in response)) {
            throw new Error("malformed results:"+":"+escape_html(JSON.stringify(result)));
        }
        if (response.error != null && response.error != "no matches") {
            throw new Error(response.error);
        }
        return response;
    }).catch(err => {
        console.log("Error:"+url);
        console.log(err);
        throw err;
   });
}

function get_json(url, div_container, func) {
    $.getJSON(url, function (result) {
        if (result == null || typeof(result) != "object" || (result instanceof Array) || (result instanceof Date) || !("data" in result)) {
            result = {
                "error":"malformed result:"+":"+escape_html(JSON.stringify(result))
                ,"data":null
            }
        }
        if (result.error != null && result.error != "no matches") {
            print_error(result.error, div_container);
        }

        return func(result);

    }).error(function(data) {
        result = {
            "error":+JSON.stringify(data)
            ,"data":null
        }
        print_error(result.error);
    });
}

function escape_html(string) {
    return string
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
 }

function print_error (message, div_container = null) {
    if (div_container == null) {
        div_container = $('#default_error_location');
        div_container.css({'visibility':'visible'});
    }
    div_container.append('<div class="asrank-result-error"> ERROR:'+message+'</div>');
}

function page_links_add(div_container, html_url, page_number, page_count, objects_total) {
    if (objects_total <= page_count) {
        return;
    }
    var page_numbers = [];
    var page_last = Math.round(objects_total/page_count);
    if (page_last*page_count < objects_total) {
        page_last += 1
    }
    var page_first = 1;

    //console.log(objects_total, page_count, page_last);

    var page_middle = page_number;
    if (page_middle > page_last) {
        page_middle = page_last - PAGE_NUMBER_RANGE;
    } else if (page_middle < page_first) {
        page_middle = page_first + PAGE_NUMBER_RANGE;
        page_numbers.push(page_number);
        if (page_first-1 > page_number) {
            page_numbers.push("..");
        }
    }

    var page_start = page_first;
    if (page_middle-PAGE_NUMBER_RANGE-1 > page_first) {
        page_start=page_middle-PAGE_NUMBER_RANGE;
        page_numbers.push(page_first, "..");
    }
    for (i=page_start;i<=page_middle;i++) {
        page_numbers.push(i);
    }

    var page_end = page_last;
    if (page_middle+PAGE_NUMBER_RANGE+1 < page_end) {
        page_end=page_middle+PAGE_NUMBER_RANGE;
    } else if (page_last < page_end) {
        page_end = page_last;
    }
    for (i=page_middle+1;i<=page_end;i++) {
        page_numbers.push(i);
    }
    if (page_end != page_last) {
        page_numbers.push("..",page_last);
    }

    if (page_middle < page_number) {
        if (page_end+1 < page_number) {
            page_numbers.push("..");
        }
        page_numbers.push(page_number);
    }
    //console.log([page_first,page_start,page_number,page_end,page_last].join(","))
    //alert([page_first,page_start,page_number,page_end,page_last].join(","))
    var page_div = $('<div class="asrank-asns-table-pages-links"></div>');
    div_container.append(page_div);

    var page_url = add_param(html_url,"page=");
		var li;
		var ul = $( '<ul class="pagination pagination-sm"></ul>' );

    $.each(page_numbers, function (i, page) {
        if (page == "..") {
            //page_div.append(' .. ');
			li = $( '<li class="page-item page-last-separator disabled"><a class="page-link" href="' + page_url + page + '">' + page + '</a></li>' );
        } else if (page == page_number) {
            //page_div.append(" "+page+" ");
		    li = $( '<li class="page-item active"><a class="page-link" href="' + page_url + page + '">' + page + '</a></li>' );
        } else {
            //page_div.append(' <a href="'+page_url+page+'">'+page+'</a> ');
		    li = $( '<li class="page-item"><a class="page-link" href="' + page_url + page + '">' + page + '</a></li>' );
        }
	    ul.append ( li );
    });

		var nav = $( '<nav></nav>' );
		nav.append ( ul );
		div_container.append ( nav );
}

function add_param(url, param) {
    var re = RegExp("\\?");
    if (re.test(url)) {
        url += "&"+param;
    } else {
        url += "?"+param;
    }
    return url;
}

// Update a parameter in the given key value; if the parameter isn't there,
// the code will add it
// @author - Mona Wong
function update_param ( url, key, value ) {
    var found = false;
    var re = RegExp ( "\\?" );
    var new_url = ""

    if ( re.test ( url ) ) {
        var split1 = url.split ( "?" );
        var split2 = split1[1].split ( "&" );

        params = []
        for ( i = 0; i < split2.length; i++ ) {
            var tmp = split2[i].split ( "=" );
            if ( tmp[0] == key ) {
                params.push(key+"="+value)
                found = true;
            } else
                params.push(split2[i]);
        }
        if ( found == false )
            params.push(key+"="+value)

        new_url = split1[0] + "?" + params.join("&");
    } else
        new_url = url + "?" + key + "=" + value;

    return ( new_url );
}


// ------------- NULL Functions
function build_sorter(url, type_current, type_default) {
    var div = $('<div>sort: </div>');
    var sort_types = ["rank", "transit_degree", "customer_cone_asns", "customer_cone_prefixes", 
        "customer_cone_addresses"];
    $.each( sort_types, function (i, type) {
        var label = type.replace(/_/g," ");

        if (i > 0) {
            div.append(', ');
        }
        if (type_current == type) {
            div.append(label);
        } else if (type_default == type) {
            div.append('<a href="'+url+'">'+label+'</a>');
        } else {
            div.append('<a href="'+url+'?sort='+type+'">'+label+'</a>');
        }
    });
    return div;
}

// ------------- NULL Functions
function null_zero(string) {
    if (string == null || string == undefined) {
        return 0;
    }
    return string;
}
function null_empty(string) {
    if (string == null || string == undefined) {
        return "";
    }
    return string;
}
function null_unknown(string) {
    if (string == null) {
        return '<span class="asrank-unknown">unknown</span>';
    }
    return string;
}

function null_zero_if_ranked(key, obj) {
    if (key in obj) {
        return obj[key];
    } else if ("rank" in obj) {
        return 0;
    }
    return undefined;
}
