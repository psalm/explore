<?php
$file_name = htmlentities($_SERVER['QUERY_STRING'] ?? '');
?>
<html>
<head>
<title>Psalm Output Explorer</title>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/6095012/css/fonts.css" />
<link rel="stylesheet" type="text/css" href="/assets/css/site.css" />
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>

<nav>
<h1><a href="https://psalm.dev">Psalm</a> Output Explorer</h1>
<h2><?php echo $file_name ?> - <span class="error_count">14 errors</span>, <span class="warning_count">18 warnings</span></h2>
</nav>

<textarea
    name="php_code"
    id="php_code"
    rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
><?php echo file_get_contents('FunctionDeclarationTransformer.php') ?></textarea>

<script>

var file_name = '<?php echo $file_name ?>';
var issues = <?php echo file_get_contents("issues.json") ?>;
var type_map = <?php echo file_get_contents("type_map.json") ?>;

var type_map_files = type_map.files;
var type_map_dictionary = type_map.references;

var file_issues = issues.filter(function (issue) {
    return issue.file_name === file_name;
});

var fetchAnnotations = function (code, callback, options, cm) {
    var mapped_issues = file_issues.map(
        function (issue) {
            return {
                severity: issue.severity === 'error' ? 'error' : 'warning',
                message: issue.message,
                from: cm.posFromIndex(issue.from),
                to: cm.posFromIndex(issue.to)
            };
        }
    );

    if (file_name in type_map_files) {
        var [file_reference_map, file_type_map] = type_map_files[file_name];

        Object.keys(file_type_map).forEach(function (from) {
            mapped_issues.push({
                severity: 'type',
                message: file_type_map[from][1],
                from: cm.posFromIndex(from),
                to: cm.posFromIndex(file_type_map[from][0])
            });
        });

        Object.keys(file_reference_map).forEach(function (from) {
            mapped_issues.push({
                severity: 'reference',
                message: file_reference_map[from][1],
                from: cm.posFromIndex(from),
                to: cm.posFromIndex(file_reference_map[from][0])
            });
        });
    }

    callback(
        mapped_issues
    );
};

var cm = CodeMirror.fromTextArea(document.getElementById("php_code"), {
    lineNumbers: true,
    matchBrackets: true,
    mode: "text/x-php",
    indentUnit: 4,
    theme: 'elegant',
    readOnly: true,
    lint: {
        getAnnotations: fetchAnnotations,
        async: true,
    }
});

CodeMirror.defineExtension("centerOnLine", function(line) { 
  var h = this.getScrollInfo().clientHeight; 
  var coords = this.charCoords({line: line, ch: 0}, "local"); 
  this.scrollTo(null, (coords.top + coords.bottom - h) / 2); 
}); 

cm.getWrapperElement().onmouseup = function(e) {
    var cursorPos = cm.indexFromPos(cm.coordsChar({ left: e.clientX, top: e.clientY }));

    if (file_name in type_map_files) {
        var [file_reference_map] = type_map_files[file_name];

        var reference = null;

        for (var from in file_reference_map) {
            if (cursorPos < from) {
                break;
            }

            var to = file_reference_map[from][0];

            if (to < cursorPos) {
                continue;
            }

            reference = file_reference_map[from][1];
        }

        if (reference && reference in type_map_dictionary) {
            var location = type_map_dictionary[reference];

            var location_parts = location.split(':');

            if (location_parts[0] === file_name) {
                cm.centerOnLine(location_parts[1]);
            } else {
                alert('Would navigate to ' + location);
            }
        }
    }
}


</script>
</body>
</html>
