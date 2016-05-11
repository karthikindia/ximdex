// Generated by CoffeeScript 1.10.0
var extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

ContentEdit.Static = (function(superClass) {
  extend(Static, superClass);

  function Static(tagName, attributes, content) {
    Static.__super__.constructor.call(this, tagName, attributes);
    this._content = content;
  }

  Static.prototype.cssTypeName = function() {
    return 'static';
  };

  Static.prototype.type = function() {
    return 'Static';
  };

  Static.prototype.typeName = function() {
    return 'Static';
  };

  Static.prototype.createDraggingDOMElement = function() {
    var helper, text;
    if (!this.isMounted()) {
      return;
    }
    helper = Static.__super__.createDraggingDOMElement.call(this);
    text = this._domElement.textContent;
    if (text.length > ContentEdit.HELPER_CHAR_LIMIT) {
      text = text.substr(0, ContentEdit.HELPER_CHAR_LIMIT);
    }
    helper.innerHTML = text;
    return helper;
  };

  Static.prototype.html = function(indent) {
    if (indent == null) {
      indent = '';
    }
    if (HTMLString.Tag.SELF_CLOSING[this._tagName]) {
      return indent + "<" + this._tagName + (this._attributesToString()) + ">";
    }
    return (indent + "<" + this._tagName + (this._attributesToString()) + ">") + ("" + this._content) + (indent + "</" + this._tagName + ">");
  };

  Static.prototype.mount = function() {
    var name, ref, value;
    this._domElement = document.createElement(this._tagName);
    ref = this._attributes;
    for (name in ref) {
      value = ref[name];
      this._domElement.setAttribute(name, value);
    }
    this._domElement.innerHTML = this._content;
    return Static.__super__.mount.call(this);
  };

  Static.prototype.blur = void 0;

  Static.prototype.focus = void 0;

  Static.prototype._onMouseDown = function(ev) {
    Static.__super__._onMouseDown.call(this, ev);
    if (this.attr('data-ce-moveable') !== void 0) {
      clearTimeout(this._dragTimeout);
      return this._dragTimeout = setTimeout((function(_this) {
        return function() {
          return _this.drag(ev.pageX, ev.pageY);
        };
      })(this), 150);
    }
  };

  Static.prototype._onMouseOver = function(ev) {
    Static.__super__._onMouseOver.call(this, ev);
    return this._removeCSSClass('ce-element--over');
  };

  Static.prototype._onMouseUp = function(ev) {
    Static.__super__._onMouseUp.call(this, ev);
    if (this._dragTimeout) {
      return clearTimeout(this._dragTimeout);
    }
  };

  Static.droppers = {
    'Static': ContentEdit.Element._dropVert
  };

  Static.fromDOMElement = function(domElement) {
    return new this(domElement.tagName, this.getDOMElementAttributes(domElement), domElement.innerHTML);
  };

  return Static;

})(ContentEdit.Element);

ContentEdit.TagNames.get().register(ContentEdit.Static, 'static');
