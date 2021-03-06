var config = {
    "graph" : {
        "linkDistance" : 125,
        "charge"       : -400,
        "height"       : 800,
        "numColors"    : 12,
        "labelPadding" : {
            "left"   : 3,
            "right"  : 3,
            "top"    : 2,
            "bottom" : 2
        },
        "labelMargin" : {
            "left"   : 3,
            "right"  : 3,
            "top"    : 2,
            "bottom" : 2
        },
        "ticksWithoutCollisions" : 50
    },
    "types" : {
        
    },
    "constraints" : [
        {
            "has"    : { "type" : "IMG" },
            "type"   : "position",
            "x"      : 0.2,
            "y"      : 0.2,
            "weight" : 0.7
        },
        {
            "has"    : { "type" : "CSS" },
            "type"   : "position",
            "x"      : 0.2,
            "y"      : 0.2,
            "weight" : 0.7
        },
        {
            "has"    : { "type" : "Document" },
            "type"   : "position",
            "x"      : 0.2,
            "y"      : 0.2,
            "weight" : 0.7
        }
    ]
};
