function Schema() {

    this.expand = function(object) {

        if (object === null) {
            return object;
        }

        if (typeof object != 'object') {
            return [object];
        }

        if (object.constructor === Array) {
            var retval = [];
            for (var i = 0; i < object.length; i++) {
                retval = retval.concat(this.expand(object[i]));
            }
            return retval;
        }

        // Expand if $any tag is present
        var variations = [];

        if (object.hasOwnProperty('$any')) {

            for (var k in object['$any']) {

                var currentOption = object['$any'][k];

                if (typeof currentOption != 'object') {
                    variations.push(currentOption);
                    continue;
                }


                /*
                Syntactic sugar:
                Track properties along side "$any" and use them in all variations:

                turn this

                {
                    someProperty: someValue,
                    $any: [
                        {
                            someOtherProperty: someValue
                        },

                        {
                            someOtherProperty: someOtherValue
                        }
                    ]
                }

                into this

                {
                    $any: [
                        {
                            someProperty: someValue,
                            someOtherProperty: someValue
                        },

                        {
                            someProperty: someValue,
                            someOtherProperty: someOtherValue
                        }
                    ]
                }
                */
                var mergedObject = {};

                for (var property in object) {
                    if (property == '$any') {
                        continue;
                    }
                    if (Object.prototype.hasOwnProperty.call(object, property)) {
                        mergedObject[property] = object[property];
                    }
                }


                for (var property in currentOption) {
                    if (Object.prototype.hasOwnProperty.call(currentOption, property)) {
                        mergedObject[property] = currentOption[property];
                    }
                }

                variations = variations.concat(this.expand(mergedObject));
            }

            return variations;
        }



        // Expand object:
        var expanded = {};
        for (var property in object) {
            if (Object.prototype.hasOwnProperty.call(object, property)) {
                expanded[property] = this.expand(object[property]);
            }
        }


        return this.combine(expanded);

    };


    this.combine = function(expansionObject) {

        var retval = [];

        for (var property in expansionObject) {
            retval = this.addProperty(retval, property, expansionObject[property]);
        }

        return retval;
    };




    /**

    This function takes an array of objects and returns
    a new array containing all possible combinations of the
    given property values:

    ex.
    arr = [
        {
            "name": "SomeName"
        },

        {
            "name": "Bar"
        }
    ]

    var result = addProperty(arr, "type", ["a", "b"]);

    result is:

    [
        {
            "name": "SomeName",
            "type": "a"
        },

        {
            "name": "SomeName",
            "type": "b"
        },

        {
            "name": "Bar",
            "type": "a"
        },

        {
            "name": "Bar",
            "type": "b"
        }
    ]

    */

    this.addProperty = function(arr, property, values) {

        var incoming = [];

        for (var key in values) {
            var object       = {};
            object[property] = values[key];
            incoming.push(object);
        }

        return this.fuse(arr, incoming);
    };


    this.fuse = function(arrayA, arrayB) {

        if (arrayA.length == 0) {
            return arrayB;
        }

        var retval = [];

        for (var i in arrayA) {
            var elementA = arrayA[i];
            for (var k in arrayB) {
                var elementB = arrayB[k];

                retval.push( this.mergeObjects(elementA, elementB) );
            }
        }

        return retval;

    };


    this.mergeObjects = function(elementA, elementB) {

        // this function returns a new object
        var retval = {};

        // with copies off al properties from elementA
        for (var property in elementA) {
            if (Object.prototype.hasOwnProperty.call(elementA, property)) {
                retval[property] = elementA[property];
            }
        }

        // overwritten with copies of all properties from elementB
        for (var property in elementB) {
            if (Object.prototype.hasOwnProperty.call(elementB, property)) {
                retval[property] = elementB[property];
            }
        }

        return retval;
    };


    this.getExample = function(schema) {

        if (typeof schema !== "object") {
            return schema;
        }

        if (schema.constructor === Array) {
            return schema.map(this.getExample);
        }

        if (schema.$type !== undefined) {
            return this.getTypeExample(schema.$type, schema);
        }

        var retval = {};
        for (property in schema) {
            retval[property] = this.getExample(schema[property]);
        }
        return retval;

    };

    this.getTypeExample = function(type, schema) {

        switch (type) {

            case "boolean":
                return Math.random() < 0.5;
            break;

            case "integer":
                return Math.floor((Math.random() * 999999) + 1);
            break;

            case "string":
                return "A random string";
            break;

            case "array":
                
                if (schema.$items === undefined) {
                    return ["An array"];
                }

                var retval = [];
                var nItems = Math.floor((Math.random() * 5) + 1);

                for (var cont = 1; cont <= nItems; cont++) {
                    retval.push(this.getExample(schema.$items));
                }

                return retval;

            break;

            default:
                return "An element of type " + type;
            break;

        }

    };

};



function Collection(items) {

    if (typeof items === "object" && items.constructor !== Array) {
        var schema = new Schema();
        items      = schema.expand(items);
    }

    this.items = items || [];

    this.push = function(item) {
        this.items.push(item);
        return this;
    };

    this.getObjectProperty = function(object, property) {

        var currentObject = object;

        var path = property.split(".");
        for (var i = 0; i < path.length; i++) {
            var currentProperty = path[i];

            if (!currentObject.hasOwnProperty(currentProperty)) {
                return undefined;
            }

            currentObject = currentObject[currentProperty];
        }


        return currentObject;
    };



    this.distinct = function(property) {

        var retval = new Collection();

        for (var k in this.items) {

            var item         = this.items[k];
            var currentValue = this.getObjectProperty(item, property);

            if (currentValue !== undefined && retval.items.indexOf(currentValue) < 0) {
                retval.push(currentValue);
            }

        };

        return retval;

    };


    this.groupBy = function(property) {

        var groups = {};

        for (var k in this.items) {

            var item  = this.items[k];
            var value = this.getObjectProperty(item, property);

            if (!value) {
                continue;
            }

            if (!groups.hasOwnProperty(value)) {
                groups[value] = new Collection();
            }

            groups[value].push(item);

        };

        return groups;

    };



    /*
    collection.match({
        first: "santiago",
        people: {
            name: "santiago"
        }
    });
    */

    this.match = function(object) {

        var matches = new Collection();

        for (var k in this.items) {

            var item = this.items[k];

            if (this.matchesSchema(item, object)) {
                matches.push(item);
            }

        };

        return matches;
    }


    this.matchesSchema = function(subject, schema) {

        if (typeof schema !== 'object') {
            return subject === schema;
        }

        if (typeof subject !== 'object') {
            return false;
        }

        for (var property in schema) {

            if (!subject.hasOwnProperty(property)) {
                return this.matchesSchema(null, schema[property]);
            }

            if (!this.matchesSchema(subject[property], schema[property])) {
                return false;
            }

        }

        return true;

    }

    this.condense = function() {

        var retval = {};

        for (var k = 0; k < this.items.length; k++) {
            retval = this.condenseObjects(retval, this.items[k]);
        }

        return retval;

    }

    this.condenseObjects = function(targetObject, sourceObject) {


        for (var property in sourceObject) {

            var value = sourceObject[property];

            if (typeof value === 'object') {

                if ( ! Object.prototype.hasOwnProperty.call(targetObject, property)) {
                    targetObject[property] = {};
                }

                targetObject[property] = this.condenseObjects(targetObject[property], value);

            } else {

                if ( ! Object.prototype.hasOwnProperty.call(targetObject, property)) {
                    targetObject[property] = [];
                }

                targetObject[property].push(value);
            }

        }


        return targetObject;

    }

}