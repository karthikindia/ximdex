// Generated by CoffeeScript 1.10.0
var extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

ContentEdit.Text = (function(superClass) {
  extend(Text, superClass);

  function Text(tagName, attributes, content) {
    Text.__super__.constructor.call(this, tagName, attributes);
    if (content instanceof HTMLString.String) {
      this.content = content;
    } else {
      this.content = new HTMLString.String(content).trim();
    }
  }

  Text.prototype.cssTypeName = function() {
    return 'text';
  };

  Text.prototype.type = function() {
    return 'Text';
  };

  Text.prototype.typeName = function() {
    return 'Text';
  };

  Text.prototype.blur = function() {
    var error, error1;
    if (this.isMounted()) {
      this._syncContent();
    }
    if (this.content.isWhitespace()) {
      if (this.parent()) {
        this.parent().detach(this);
      }
    } else if (this.isMounted()) {
      try {
        this._domElement.blur();
      } catch (error1) {
        error = error1;
      }
      this._domElement.removeAttribute('contenteditable');
    }
    return Text.__super__.blur.call(this);
  };

  Text.prototype.createDraggingDOMElement = function() {
    var helper, text;
    if (!this.isMounted()) {
      return;
    }
    helper = Text.__super__.createDraggingDOMElement.call(this);
    text = HTMLString.String.encode(this._domElement.textContent);
    if (text.length > ContentEdit.HELPER_CHAR_LIMIT) {
      text = text.substr(0, ContentEdit.HELPER_CHAR_LIMIT);
    }
    helper.innerHTML = text;
    return helper;
  };

  Text.prototype.drag = function(x, y) {
    this.storeState();
    this._domElement.removeAttribute('contenteditable');
    return Text.__super__.drag.call(this, x, y);
  };

  Text.prototype.drop = function(element, placement) {
    Text.__super__.drop.call(this, element, placement);
    return this.restoreState();
  };

  Text.prototype.focus = function(supressDOMFocus) {
    if (this.isMounted()) {
      this._domElement.setAttribute('contenteditable', '');
    }
    return Text.__super__.focus.call(this, supressDOMFocus);
  };

  Text.prototype.html = function(indent) {
    var content;
    if (indent == null) {
      indent = '';
    }
    if (!this._lastCached || this._lastCached < this._modified) {
      content = this.content.copy();
      content.optimize();
      this._lastCached = Date.now();
      this._cached = content.html();
    }
    return (indent + "<" + this._tagName + (this._attributesToString()) + ">\n") + ("" + indent + ContentEdit.INDENT + this._cached + "\n") + (indent + "</" + this._tagName + ">");
  };

  Text.prototype.mount = function() {
    var name, ref, value;
    this._domElement = document.createElement(this._tagName);
    ref = this._attributes;
    for (name in ref) {
      value = ref[name];
      this._domElement.setAttribute(name, value);
    }
    this.updateInnerHTML();
    return Text.__super__.mount.call(this);
  };

  Text.prototype.restoreState = function() {
    if (!this._savedSelection) {
      return;
    }
    if (!(this.isMounted() && this.isFocused())) {
      this._savedSelection = void 0;
      return;
    }
    this._domElement.setAttribute('contenteditable', '');
    this._addCSSClass('ce-element--focused');
    if (document.activeElement !== this.domElement()) {
      this.domElement().focus();
    }
    this._savedSelection.select(this._domElement);
    return this._savedSelection = void 0;
  };

  Text.prototype.selection = function(selection) {
    if (selection === void 0) {
      if (this.isMounted()) {
        return ContentSelect.Range.query(this._domElement);
      } else {
        return new ContentSelect.Range(0, 0);
      }
    }
    return selection.select(this._domElement);
  };

  Text.prototype.storeState = function() {
    if (!(this.isMounted() && this.isFocused())) {
      return;
    }
    return this._savedSelection = ContentSelect.Range.query(this._domElement);
  };

  Text.prototype.updateInnerHTML = function() {
    this._domElement.innerHTML = this.content.html();
    ContentSelect.Range.prepareElement(this._domElement);
    return this._flagIfEmpty();
  };

  Text.prototype._onKeyDown = function(ev) {
    switch (ev.keyCode) {
      case 40:
        return this._keyDown(ev);
      case 37:
        return this._keyLeft(ev);
      case 39:
        return this._keyRight(ev);
      case 38:
        return this._keyUp(ev);
      case 9:
        return this._keyTab(ev);
      case 8:
        return this._keyBack(ev);
      case 46:
        return this._keyDelete(ev);
      case 13:
        return this._keyReturn(ev);
    }
  };

  Text.prototype._onKeyUp = function(ev) {
    Text.__super__._onKeyUp.call(this, ev);
    return this._syncContent();
  };

  Text.prototype._onMouseDown = function(ev) {
    Text.__super__._onMouseDown.call(this, ev);
    clearTimeout(this._dragTimeout);
    this._dragTimeout = setTimeout((function(_this) {
      return function() {
        return _this.drag(ev.pageX, ev.pageY);
      };
    })(this), ContentEdit.DRAG_HOLD_DURATION);
    if (this.content.length() === 0 && ContentEdit.Root.get().focused() === this) {
      ev.preventDefault();
      if (document.activeElement !== this._domElement) {
        this._domElement.focus();
      }
      return new ContentSelect.Range(0, 0).select(this._domElement);
    }
  };

  Text.prototype._onMouseMove = function(ev) {
    if (this._dragTimeout) {
      clearTimeout(this._dragTimeout);
    }
    return Text.__super__._onMouseMove.call(this, ev);
  };

  Text.prototype._onMouseOut = function(ev) {
    if (this._dragTimeout) {
      clearTimeout(this._dragTimeout);
    }
    return Text.__super__._onMouseOut.call(this, ev);
  };

  Text.prototype._onMouseUp = function(ev) {
    if (this._dragTimeout) {
      clearTimeout(this._dragTimeout);
    }
    return Text.__super__._onMouseUp.call(this, ev);
  };

  Text.prototype._keyBack = function(ev) {
    var previous, selection;
    selection = ContentSelect.Range.query(this._domElement);
    if (!(selection.get()[0] === 0 && selection.isCollapsed())) {
      return;
    }
    ev.preventDefault();
    previous = this.previousContent();
    this._syncContent();
    if (previous) {
      return previous.merge(this);
    }
  };

  Text.prototype._keyDelete = function(ev) {
    var next, selection;
    selection = ContentSelect.Range.query(this._domElement);
    if (!(this._atEnd(selection) && selection.isCollapsed())) {
      return;
    }
    ev.preventDefault();
    next = this.nextContent();
    if (next) {
      return this.merge(next);
    }
  };

  Text.prototype._keyDown = function(ev) {
    return this._keyRight(ev);
  };

  Text.prototype._keyLeft = function(ev) {
    var previous, selection;
    selection = ContentSelect.Range.query(this._domElement);
    if (!(selection.get()[0] === 0 && selection.isCollapsed())) {
      return;
    }
    ev.preventDefault();
    previous = this.previousContent();
    if (previous) {
      previous.focus();
      selection = new ContentSelect.Range(previous.content.length(), previous.content.length());
      return selection.select(previous.domElement());
    } else {
      return ContentEdit.Root.get().trigger('previous-region', this.closest(function(node) {
        return node.type() === 'Region';
      }));
    }
  };

  Text.prototype._keyReturn = function(ev) {
    var element, insertAt, lineBreakStr, selection, tail, tip;
    ev.preventDefault();
    if (this.content.isWhitespace()) {
      return;
    }
    ContentSelect.Range.query(this._domElement);
    selection = ContentSelect.Range.query(this._domElement);
    tip = this.content.substring(0, selection.get()[0]);
    tail = this.content.substring(selection.get()[1]);
    if (ev.shiftKey) {
      insertAt = selection.get()[0];
      lineBreakStr = '<br/>';
      if (this.content.length() === insertAt) {
        if (!this.content.characters[insertAt - 1].isTag('br')) {
          lineBreakStr = '<br/><br/>';
        }
      }
      this.content = this.content.insert(insertAt, new HTMLString.String(lineBreakStr, true), true);
      this.updateInnerHTML();
      insertAt += 1;
      selection = new ContentSelect.Range(insertAt, insertAt);
      selection.select(this.domElement());
      return;
    }
    this.content = tip.trim();
    this.updateInnerHTML();
    element = new this.constructor('p', {}, tail.trim());
    this.parent().attach(element, this.parent().children.indexOf(this) + 1);
    if (tip.length()) {
      element.focus();
      selection = new ContentSelect.Range(0, 0);
      selection.select(element.domElement());
    } else {
      selection = new ContentSelect.Range(0, tip.length());
      selection.select(this._domElement);
    }
    return this.taint();
  };

  Text.prototype._keyRight = function(ev) {
    var next, selection;
    selection = ContentSelect.Range.query(this._domElement);
    if (!(this._atEnd(selection) && selection.isCollapsed())) {
      return;
    }
    ev.preventDefault();
    next = this.nextContent();
    if (next) {
      next.focus();
      selection = new ContentSelect.Range(0, 0);
      return selection.select(next.domElement());
    } else {
      return ContentEdit.Root.get().trigger('next-region', this.closest(function(node) {
        return node.type() === 'Region';
      }));
    }
  };

  Text.prototype._keyTab = function(ev) {
    return ev.preventDefault();
  };

  Text.prototype._keyUp = function(ev) {
    return this._keyLeft(ev);
  };

  Text.prototype._atEnd = function(selection) {
    var atEnd;
    atEnd = selection.get()[0] === this.content.length();
    if (selection.get()[0] === this.content.length() - 1 && this.content.characters[this.content.characters.length - 1].isTag('br')) {
      atEnd = true;
    }
    return atEnd;
  };

  Text.prototype._flagIfEmpty = function() {
    if (this.content.length() === 0) {
      return this._addCSSClass('ce-element--empty');
    } else {
      return this._removeCSSClass('ce-element--empty');
    }
  };

  Text.prototype._syncContent = function(ev) {
    var newSnaphot, snapshot;
    snapshot = this.content.html();
    this.content = new HTMLString.String(this._domElement.innerHTML, this.content.preserveWhitespace());
    newSnaphot = this.content.html();
    if (snapshot !== newSnaphot) {
      this.taint();
    }
    return this._flagIfEmpty();
  };

  Text.droppers = {
    'Static': ContentEdit.Element._dropVert,
    'Text': ContentEdit.Element._dropVert
  };

  Text.mergers = {
    'Text': function(element, target) {
      var offset;
      offset = target.content.length();
      if (element.content.length()) {
        target.content = target.content.concat(element.content);
      }
      if (target.isMounted()) {
        target.updateInnerHTML();
      }
      target.focus();
      new ContentSelect.Range(offset, offset).select(target._domElement);
      if (element.parent()) {
        element.parent().detach(element);
      }
      return target.taint();
    }
  };

  Text.fromDOMElement = function(domElement) {
    return new this(domElement.tagName, this.getDOMElementAttributes(domElement), domElement.innerHTML.replace(/^\s+|\s+$/g, ''));
  };

  return Text;

})(ContentEdit.Element);

ContentEdit.TagNames.get().register(ContentEdit.Text, 'address', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p');

ContentEdit.PreText = (function(superClass) {
  extend(PreText, superClass);

  function PreText(tagName, attributes, content) {
    if (content instanceof HTMLString.String) {
      this.content = content;
    } else {
      this.content = new HTMLString.String(content, true);
    }
    ContentEdit.Element.call(this, tagName, attributes);
  }

  PreText.prototype.cssTypeName = function() {
    return 'pre-text';
  };

  PreText.prototype.type = function() {
    return 'PreText';
  };

  PreText.prototype.typeName = function() {
    return 'Preformatted';
  };

  PreText.prototype.html = function(indent) {
    var content;
    if (indent == null) {
      indent = '';
    }
    if (!this._lastCached || this._lastCached < this._modified) {
      content = this.content.copy();
      content.optimize();
      this._lastCached = Date.now();
      this._cached = content.html();
    }
    return (indent + "<" + this._tagName + (this._attributesToString()) + ">") + (this._cached + "</" + this._tagName + ">");
  };

  PreText.prototype.updateInnerHTML = function() {
    var html;
    html = this.content.html();
    html += '\n';
    this._domElement.innerHTML = html;
    ContentSelect.Range.prepareElement(this._domElement);
    return this._flagIfEmpty();
  };

  PreText.prototype._onKeyUp = function(ev) {
    var html, newSnaphot, snapshot;
    snapshot = this.content.html();
    html = this._domElement.innerHTML.replace(/[\n]$/, '');
    this.content = new HTMLString.String(html, this.content.preserveWhitespace());
    newSnaphot = this.content.html();
    if (snapshot !== newSnaphot) {
      this.taint();
    }
    return this._flagIfEmpty();
  };

  PreText.prototype._keyReturn = function(ev) {
    var cursor, selection, tail, tip;
    ev.preventDefault();
    selection = ContentSelect.Range.query(this._domElement);
    cursor = selection.get()[0] + 1;
    if (selection.get()[0] === 0 && selection.isCollapsed()) {
      this.content = new HTMLString.String('\n', true).concat(this.content);
    } else if (this._atEnd(selection) && selection.isCollapsed()) {
      this.content = this.content.concat(new HTMLString.String('\n', true));
    } else if (selection.get()[0] === 0 && selection.get()[1] === this.content.length()) {
      this.content = new HTMLString.String('\n', true);
      cursor = 0;
    } else {
      tip = this.content.substring(0, selection.get()[0]);
      tail = this.content.substring(selection.get()[1]);
      this.content = tip.concat(new HTMLString.String('\n', true), tail);
    }
    this.updateInnerHTML();
    selection.set(cursor, cursor);
    selection.select(this._domElement);
    return this.taint();
  };

  PreText.droppers = {
    'PreText': ContentEdit.Element._dropVert,
    'Static': ContentEdit.Element._dropVert,
    'Text': ContentEdit.Element._dropVert
  };

  PreText.mergers = {};

  PreText.fromDOMElement = function(domElement) {
    return new this(domElement.tagName, this.getDOMElementAttributes(domElement), domElement.innerHTML);
  };

  return PreText;

})(ContentEdit.Text);

ContentEdit.TagNames.get().register(ContentEdit.PreText, 'pre');
