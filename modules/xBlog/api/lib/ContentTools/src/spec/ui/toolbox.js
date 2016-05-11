// Generated by CoffeeScript 1.10.0
describe('ContentTools.ToolboxUI', function() {
  var div, editor;
  div = null;
  editor = null;
  beforeEach(function() {
    div = document.createElement('div');
    div.setAttribute('class', 'editable');
    div.setAttribute('id', 'foo');
    div.innerHTML = '<p>bar</p><img scr="test.png">';
    document.body.appendChild(div);
    editor = ContentTools.EditorApp.get();
    editor.init('.editable');
    return editor.start();
  });
  afterEach(function() {
    editor.stop();
    editor.destroy();
    return document.body.removeChild(div);
  });
  describe('ContentTools.ToolboxUI()', function() {
    return it('should return an instance of a ToolboxUI', function() {
      var toolbox;
      toolbox = new ContentTools.ToolboxUI([]);
      return expect(toolbox instanceof ContentTools.ToolboxUI).toBe(true);
    });
  });
  describe('ContentTools.ToolboxUI.isDragging()', function() {
    return it('should return true if the ToolboxUI is currently being dragged', function() {
      var mouseDownEvent, mouseUpEvent, toolbox;
      toolbox = editor._toolbox;
      expect(toolbox.isDragging()).toBe(false);
      mouseDownEvent = document.createEvent('CustomEvent');
      mouseDownEvent.initCustomEvent('mousedown', false, false, null);
      toolbox._domGrip.dispatchEvent(mouseDownEvent);
      expect(toolbox.isDragging()).toBe(true);
      mouseUpEvent = document.createEvent('CustomEvent');
      mouseUpEvent.initCustomEvent('mouseup', false, false, null);
      document.dispatchEvent(mouseUpEvent);
      return expect(toolbox.isDragging()).toBe(false);
    });
  });
  describe('ContentTools.ToolboxUI.hide()', function() {
    return it('should remove all event bindings before the toolbox is hidden', function() {
      var toolbox;
      toolbox = editor._toolbox;
      spyOn(toolbox, '_removeDOMEventListeners');
      toolbox.hide();
      return expect(toolbox._removeDOMEventListeners).toHaveBeenCalled();
    });
  });
  describe('ContentTools.ToolboxUI.tools()', function() {
    it('should return the list of tools that populate the toolbox', function() {
      var toolbox;
      toolbox = editor._toolbox;
      return expect(toolbox.tools()).toEqual(ContentTools.DEFAULT_TOOLS);
    });
    return it('should set the list of tools that populate the toolbox', function() {
      var customTools, toolbox;
      toolbox = editor._toolbox;
      customTools = [['bold', 'italic', 'link']];
      toolbox.tools(customTools);
      return expect(toolbox.tools()).toEqual(customTools);
    });
  });
  describe('ContentTools.ToolboxUI.mount()', function() {
    it('should mount the component', function() {
      var toolbox;
      toolbox = new ContentTools.ToolboxUI([]);
      editor.attach(toolbox);
      toolbox.mount();
      return expect(toolbox.isMounted()).toBe(true);
    });
    it('should restore the position of the component to any previously saved state', function() {
      var toolbox;
      window.localStorage.setItem('ct-toolbox-position', '7,7');
      toolbox = new ContentTools.ToolboxUI([]);
      editor.attach(toolbox);
      toolbox.mount();
      expect(toolbox.domElement().style.left).toBe('7px');
      return expect(toolbox.domElement().style.top).toBe('7px');
    });
    return it('should always be contained within the viewport', function() {
      var toolbox;
      window.localStorage.setItem('ct-toolbox-position', '-7,-7');
      toolbox = new ContentTools.ToolboxUI([]);
      editor.attach(toolbox);
      toolbox.mount();
      expect(toolbox.domElement().style.left).toBe('');
      return expect(toolbox.domElement().style.top).toBe('');
    });
  });
  describe('ContentTools.ToolboxUI.updateTools()', function() {
    return it('should refresh all tool UIs in the toolbox', function(done) {
      var checkUpdated, element, region, toolbox;
      toolbox = editor._toolbox;
      region = editor.regions()['foo'];
      element = region.children[0];
      expect(toolbox._toolUIs['heading'].disabled()).toBe(true);
      element.focus();
      checkUpdated = function() {
        expect(toolbox._toolUIs['heading'].disabled()).toBe(false);
        return done();
      };
      return setTimeout(checkUpdated, 500);
    });
  });
  return describe('ContentTools.ToolboxUI > Keyboard short-cuts', function() {
    it('should allow a non-content element to be removed with the delete key short-cut', function() {
      var element, keyDownEvent, region, toolbox;
      toolbox = editor._toolbox;
      region = editor.regions()['foo'];
      element = region.children[1];
      element.focus();
      keyDownEvent = document.createEvent('CustomEvent');
      keyDownEvent.initCustomEvent('keydown', false, false, null);
      keyDownEvent.keyCode = 46;
      window.dispatchEvent(keyDownEvent);
      return expect(region.children.length).toBe(1);
    });
    it('should allow a undo to be triggered with Ctrl-z key short-cut', function() {
      var element, keyDownEvent, region, toolbox;
      toolbox = editor._toolbox;
      region = editor.regions()['foo'];
      element = region.children[1];
      region.detach(element);
      spyOn(ContentTools.Tools.Undo, 'canApply');
      keyDownEvent = document.createEvent('CustomEvent');
      keyDownEvent.initCustomEvent('keydown', false, false, null);
      keyDownEvent.keyCode = 90;
      keyDownEvent.ctrlKey = true;
      window.dispatchEvent(keyDownEvent);
      return expect(ContentTools.Tools.Undo.canApply).toHaveBeenCalled();
    });
    return it('should allow a redo to be triggered with Ctrl-Shift-z key short-cut', function() {
      var element, keyDownEvent, region, toolbox;
      toolbox = editor._toolbox;
      region = editor.regions()['foo'];
      element = region.children[1];
      region.detach(element);
      ContentTools.Tools.Undo.apply(null, null, function() {});
      region = editor.regions()['foo'];
      expect(region.children.length).toBe(2);
      spyOn(ContentTools.Tools.Redo, 'canApply');
      keyDownEvent = document.createEvent('CustomEvent');
      keyDownEvent.initCustomEvent('keydown', false, false, null);
      keyDownEvent.keyCode = 90;
      keyDownEvent.ctrlKey = true;
      keyDownEvent.shiftKey = true;
      window.dispatchEvent(keyDownEvent);
      return expect(ContentTools.Tools.Redo.canApply).toHaveBeenCalled();
    });
  });
});

describe('ContentTools.ToolboxUI', function() {
  var div, editor;
  div = null;
  editor = null;
  beforeEach(function() {
    div = document.createElement('div');
    div.setAttribute('class', 'editable');
    div.setAttribute('id', 'foo');
    div.innerHTML = '<p>bar</p><img scr="test.png">';
    document.body.appendChild(div);
    editor = ContentTools.EditorApp.get();
    return editor.init('.editable');
  });
  afterEach(function() {
    editor.destroy();
    return document.body.removeChild(div);
  });
  describe('ContentTools.ToolUI()', function() {
    return it('should return an instance of a ToolUI', function() {
      var tool;
      tool = new ContentTools.ToolUI(ContentTools.ToolShelf.fetch('bold'));
      return expect(tool instanceof ContentTools.ToolUI).toBe(true);
    });
  });
  describe('ContentTools.ToolUI.disabled()', function() {
    return it('should set/get the disabled state for the tool', function() {
      var tool;
      tool = new ContentTools.ToolUI(ContentTools.ToolShelf.fetch('bold'));
      expect(tool.disabled()).toBe(false);
      tool.disabled(true);
      return expect(tool.disabled()).toBe(true);
    });
  });
  describe('ContentTools.ToolUI.apply()', function() {
    return it('should apply the tool associated with the component', function() {
      var element, region, tool;
      tool = new ContentTools.ToolUI(ContentTools.ToolShelf.fetch('heading'));
      region = new ContentEdit.Region(document.querySelectorAll('.editable')[0]);
      element = region.children[0];
      tool.apply(element);
      return expect(element.tagName()).toBe('h1');
    });
  });
  describe('ContentTools.Tool.mount()', function() {
    return it('should mount the component', function() {
      var tool;
      tool = new ContentTools.ToolUI(ContentTools.ToolShelf.fetch('bold'));
      editor.attach(tool);
      tool.mount(editor.domElement());
      return expect(tool.isMounted()).toBe(true);
    });
  });
  return describe('ContentTools.Tool.update()', function() {
    return it('should update the state of the tool based on the currently focused element and content selection', function() {
      var element, region, tool;
      tool = new ContentTools.ToolUI(ContentTools.ToolShelf.fetch('heading'));
      region = new ContentEdit.Region(document.querySelectorAll('.editable')[0]);
      element = region.children[0];
      tool.update();
      expect(tool.disabled()).toBe(true);
      tool.update(element);
      return expect(tool.disabled()).toBe(false);
    });
  });
});
