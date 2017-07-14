<!doctype html>
<html ng-app="phidias-specification">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">

        <title>Documentación</title>

        <link href='https://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
        <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js" type="text/javascript"></script>

        <style type="text/css">
        html {
            font-family: 'Droid Sans', sans-serif;
            box-sizing: border-box;
        }

        *, *:before, *:after {
            box-sizing: inherit;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4 {
            font-weight: normal;
        }

        pre {
            margin: 0;
            padding: 0;
        }

        main {
            display: block;
            margin: 32px 0 0 32px;
            padding: 0;
            max-width: 1024px;
        }

        [phidias-json-schema] {
            display: inline-block;
            vertical-align: top;
        }

        #search {
        }

        #search input {
            border: 0;
            width: 100%;
            font-size: 1.3em;
            line-height: 1.3em;

            border-bottom: 1px solid #ccc;
            margin-bottom: 16px;

            outline: none;
        }

        #search input:focus {
            border-bottom: 2px solid #666;
        }

        summary {
            display: block;
            cursor: pointer;
            outline: none;
        }

        details.drawer > summary {
            padding: 8px 16px;
        }

        details.drawer > summary:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        details.drawer > summary::-webkit-details-marker {
            display: none;
        }

        details.drawer[open] {
            margin-bottom: 32px;
        }

        details.drawer[open] > summary {
            background-color: rgba(0, 0, 0, 0.08);
        }


        .module {
            display: block;
            margin: 0 0 1em 0;
            padding: 0;
        }

        .module > .resources {
            margin-left: 16px;
        }

        .module > summary h2,
        .module > summary p {
            margin: 0;
            padding: 0;
            font-weight: normal;;
        }


        .resource {
            display: block;
            margin: 0.5em 0;
            padding: 0;
        }

        .resource > details > summary h2,
        .resource > details > summary p {
            margin: 0;
            padding: 0;

            font-weight: normal;
        }

        .resource summary p {
            color: #655;
        }


        .resource table {
            width: 100%;
            margin: 0;
        }

        .resource table td {
            vertical-align: top;
        }

        .resource table td:first-child {
            font-weight: bold;
            width: 120px;
        }

        .resource table p {
            margin: 0;
            color: #333;
        }

        .resource .exchanges {
            margin: 0;
            background-color: rgba(0,0,0, 0.08);
        }



        .exchange > summary {
            padding: 12px;
        }

        .exchange > summary:hover,
        .exchange[open] > summary {
            background-color: rgba(0,0,0, 0.08);
        }

        .exchange > summary::-webkit-details-marker {
            display: none;
        }


        .exchange > summary strong {

            display: inline-block;
            text-align: center;
            padding: 4px;
            width: 60px;
            margin-right: 1em;

            background-color: #666;
            color: #fff;
            font-weight: normal;
        }

        .exchange.get > summary strong {
            background-color: #4dbcd4;
        }

        .exchange.post > summary strong {
            background-color: #b6c72b;
        }

        .exchange.put > summary strong {
            background-color: #b6c72b;
        }

        .exchange.delete > summary strong {
            background-color: #f34541;
        }

        .exchange .request,
        .exchange .response {
            padding: 18px 32px;
        }


        .exchange .request .url,
        .exchange .request .header,
        .exchange .request .body,
        .exchange .response {
            font-family: Courier, sans-serif;
        }


        .exchange p {
            margin: 0;
        }

        .exchange p em {
            font-weight: bold;
            font-style: normal;
        }

        .exchange .body {
            margin-top: 1em;
        }

        .exchange .request {
            background-color: rgba(0,0,0, 0.08);
        }

        .exchange .response {
            padding-bottom: 32px;
            background: #5a615e;
            color: #ddd;
        }

        .request > .url > summary {
            padding: 0.5em 0;
        }

        .request > .url > summary::-webkit-details-marker {
            display: none;
        }

        .request .url .method {
            text-transform: uppercase;
            font-weight: bold;
        }

        .request .url .querystring:before {
            content: '?';
        }

        .request .url .querystring {
            display: inline;
            list-style: none;
            margin: 0;
            padding: 0;

            color: #777;
        }

        .request .url .querystring li {
            display: inline;
        }

        .request .url .querystring li:after {
            content: '&';
        }


        .request .attributes,
        .request .parameters {
            padding: 16px;
        }


        .resource .resources {
            /*margin: 0 0 0 4px;*/
            margin: 0;
            padding: 0;
        }

        </style>


        <script type="text/javascript">

        angular.module("phidias-specification",[]);
        angular.module("phidias-specification").controller("mainController", mainController);
        angular.module("phidias-specification").filter('findResource', findResource);
        angular.module("phidias-specification").directive('phidiasJsonSchema', phidiasJsonSchema);


        function phidiasJsonSchema()
        {
            return {

                restrict: 'A',

                scope: {
                    schema: '=phidiasJsonSchema'
                },

                controllerAs: 'vm',
                bindToController: true,

                template: '<pre ng-bind="vm.explanation"></pre>',

                controller: function() {
                    var vm          = this;
                    var explanation = getExplanation(vm.schema);
                    vm.explanation  = typeof explanation === 'object' ? angular.toJson(explanation, 2) : explanation;
                }

            };
        }

        function getExplanation(schema)
        {
            if (typeof schema !== 'object') {
                return schema;
            }

            if (schema.constructor === Array) {
                var retval = [];
                for (var i = 0; i < schema.length; i++) {
                    retval.push( getExplanation(schema[i]) );
                }
                return retval;
            }

            switch (schema.$type) {

                case 'array':
                    return schema.$items === undefined ? [] : [getExplanation(schema.$items)];
                break;

                case 'object':
                case undefined:

                    var retval = {};
                    var foundPropertiesCount = 0;

                    for (var property in schema) {
                        if (schema.hasOwnProperty(property) && property.substring(0, 1) !== '$') {
                            retval[property] = getExplanation(schema[property]);
                            foundPropertiesCount++;
                        }
                    }

                    if (foundPropertiesCount > 0) {
                        return retval;
                    }

                break;

            }

            return schema.$title ? schema.$title : schema.$type;
        }


        function findResource()
        {
            return function(items, query) {

                var filtered = [];

                for (var i = 0; i < items.length; i++) {
                    if (resourceMatches(items[i], query)) {
                        filtered.push(items[i]);
                    }
                }

                return filtered;

            }
        }

        function resourceMatches(resource, query)
        {
            if (resource.exchanges === undefined || !resource.exchanges.length) {
                return false;
            }

            if (textMatches(resource.title, query) || textMatches(resource.description, query)) {
                return true;
            }

            if (urlMatches(resource.url, query)) {
                return true;
            }

            for (var i = 0; i < resource.exchanges.length; i++) {
                if (exchangeMatches(resource.exchanges[i], query)) {
                    return true;
                }
            }

            return false;
        }

        function textMatches(text, query)
        {
            if (text === undefined) {
                return false;
            }

            var words = query.split(' ');

            for (var i = 0; i < words.length; i++) {
                if (text.indexOf(words[i]) == -1) {
                    return false;
                }
            }

            return true;
        }

        function urlMatches(url, query)
        {
            if (url === undefined) {
                return false;
            }

            var urlParts   = url.replace(/\/\/*/gm, '/').replace(/^\/+|\/+$/gm,'').split('/');
            var queryParts = query.replace(/\/\/*/gm, '/').replace(/^\/+|\/+$/gm,'').replace(/ /gm, '').split('/');

            for (var i = 0; i < queryParts.length; i++) {

                if (urlParts[i] === undefined) {
                    return false;
                }

                if (urlParts[i].substring(0, 1) == '{') {
                    continue;
                }

                if (urlParts[i].substring(0, queryParts[i].length) != queryParts[i]) {
                    return false;
                }
            }

            return true;
        }

        function exchangeMatches(exchange, query)
        {
            if ( textMatches(exchange.title, query) || textMatches(exchange.description, query) ) {
                return true;
            }

            return false;
        }



        mainController.$inject = ["$scope"];
        function mainController($scope)
        {

            var vm       = this;
            vm.modules   = <?= json_encode($output) ?>;

            vm.allResources = [];

            for (var i = 0; i < vm.modules.length; i++) {
                pushResource(vm.allResources, vm.modules[i]);
            }
        }

        function pushResource(resourceCollection, resource, parent)
        {
            if (resource.url !== undefined) {

                resource.url = '/'+resource.url
                    .replace(/\/\/*/gm, '/')    // remove double slashes
                    .replace(/^\/+|\/+$/gm,''); // remove trailing slashes

                if (parent !== undefined && parent.url !== undefined) {
                    resource.url = parent.url + resource.url;
                }
            }

            if (parent !== undefined && parent.attributes !== undefined) {
                resource.attributes = angular.merge(resource.attributes || {}, parent.attributes);
            }

            var copy = {};
            for (var property in resource) {
                if (property != 'resources') {
                    copy[property] = resource[property];
                }
            }

            resourceCollection.push(copy);

            if (resource.hasOwnProperty('resources')) {
                for (var i = 0; i < resource.resources.length; i++) {
                    pushResource(resourceCollection, resource.resources[i], resource);
                }
            }
        }

        </script>
    </head>

    <body ng-controller="mainController as vm">

        <main>
            <h1>Documentación</h1>

            <script type="text/ng-template" id="resource.html">
                <div class="resource">
                    <details class="drawer" ng-open="isOpen">
                        <summary>
                            <p ng-bind="resource.url"></p>
                            <h2 ng-bind="resource.title"></h2>
                        </summary>

                        <div>

                            <div class="exchanges" ng-if="resource.exchanges">

                                <details class="exchange {{exchange.request.method}}" ng-repeat="exchange in resource.exchanges">

                                    <summary>
                                        <strong ng-bind="exchange.request.method || '!'"></strong>
                                        <span ng-bind="exchange.title || resource.url"></span>
                                    </summary>

                                    <div class="request">

                                        <details class="url" ng-show="exchange.request.method">
                                            <summary>
                                                <span class="method" ng-bind="exchange.request.method"></span>
                                                <span class="url" ng-bind="resource.url"></span>
                                                <ul class="querystring" ng-if="exchange.request.parameters">
                                                    <li ng-repeat="(parameterName, parameterData) in exchange.request.parameters">{{parameterName}}=...</li>
                                                </ul>
                                            </summary>

                                            <table class="attributes" ng-if="resource.attributes">
                                                <tbody>
                                                    <tr ng-repeat="(attributeName, attributeData) in resource.attributes">
                                                        <td ng-bind="attributeName"></td>
                                                        <td phidias-json-schema="attributeData"></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <table class="parameters" ng-if="exchange.request.parameters">
                                                <tbody>
                                                    <tr ng-repeat="(parameterName, parameterData) in exchange.request.parameters">
                                                        <td ng-bind="parameterName"></td>
                                                        <td phidias-json-schema="parameterData"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </details>

                                        <p class="header" ng-repeat="(property, value) in exchange.request.headers">
                                            <em ng-bind="property"></em>:
                                            <span phidias-json-schema="value"></span>
                                        </p>

                                        <div class="body" ng-if="exchange.request.body" phidias-json-schema="exchange.request.body"></div>
                                    </div>

                                    <div class="response">
                                        <p class="code" ng-bind="(exchange.response.code || '200') + ' ' + (exchange.response.reason || 'OK')"></p>

                                        <p class="header" ng-repeat="(property, value) in exchange.response.headers">
                                            <em ng-bind="property"></em>:
                                            <span phidias-json-schema="value"></span>
                                        </p>

                                        <div class="body" ng-if="exchange.response.body" phidias-json-schema="exchange.response.body"></div>
                                    </div>

                                </details>

                            </div>
                        </div>
                    </details>

                    <div class="resources" ng-if="resource.resources" ng-init="baseUrl = resource.url">
                        <div ng-include="'resource.html'" ng-repeat="resource in resource.resources"></div>
                    </div>

                </div>
            </script>


            <div id="search">
                <input type="text" placeholder="Buscar ..." ng-model="search" />

                <div class="results" ng-if="!!search.length">
                    <div ng-repeat="resource in vm.allResources|findResource:search">
                        <div ng-include="'resource.html'" onload="isOpen = true"></div>
                    </div>
                </div>
            </div>

            <div ng-repeat="module in vm.modules" ng-show="!search">

                <details class="module drawer">
                    <summary>
                        <h2 ng-bind="module.title"></h2>
                        <p ng-bind="module.description"></p>
                    </summary>

                    <div class="resources" ng-if="module.resources">
                        <div ng-include="'resource.html'" ng-repeat="resource in module.resources"></div>
                    </div>

                </details>

            </div>

        </main>
    </body>

</html>