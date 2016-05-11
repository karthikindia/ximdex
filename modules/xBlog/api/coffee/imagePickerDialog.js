// Generated by CoffeeScript 1.10.0
var extend = function(child, parent) { for (var key in parent) { if (hasProp.call(parent, key)) child[key] = parent[key]; } function ctor() { this.constructor = child; } ctor.prototype = parent.prototype; child.prototype = new ctor(); child.__super__ = parent.prototype; return child; },
  hasProp = {}.hasOwnProperty;

ContentTools.Tools.ImagePicker = (function(superClass) {
  extend(ImagePicker, superClass);

  function ImagePicker() {
    return ImagePicker.__super__.constructor.apply(this, arguments);
  }

  ContentTools.ToolShelf.stow(ImagePicker, 'imagePicker');

  ImagePicker.label = 'ImagePicker';

  ImagePicker.icon = 'imagePicker';

  ImagePicker.canApply = function(element, selection) {
    return element.type() !== 'Intro';
  };

  ImagePicker.apply = function(element, selection, callback) {
    var app, dialog, modal;
    if (element.storeState) {
      element.storeState();
    }
    app = ContentTools.EditorApp.get();
    modal = new ContentTools.ModalUI();
    dialog = new ContentTools.ImagePickerDialog();
    dialog.bind('cancel', (function(_this) {
      return function() {
        dialog.unbind('cancel');
        modal.hide();
        dialog.hide();
        if (element.restoreState) {
          element.restoreState();
        }
        return callback(false);
      };
    })(this));
    dialog.bind('ok', (function(_this) {
      return function(imageURL, imageSize, imageAttrs) {
        var image, index, node, parent, parentStyle, ref, rel, widthParent;
        dialog.unbind('save');
        if (!imageAttrs) {
          imageAttrs = {};
        }
        imageAttrs.src = imageURL;
        imageAttrs.width = imageSize[0] != null ? imageSize[0] : 9999999;
        imageAttrs.height = imageSize[1] != null ? imageSize[1] : 9999999;
        console.log(imageAttrs);
        ref = _this._insertAt(element), node = ref[0], index = ref[1];
        parent = node.parent();
        parentStyle = window.getComputedStyle(parent._domElement);
        widthParent = parseInt(parentStyle.width);
        widthParent -= parseInt(parentStyle.paddingLeft);
        widthParent -= parseInt(parentStyle.paddingRight);
        if (widthParent < imageAttrs.width) {
          rel = widthParent / imageAttrs.width;
          imageAttrs.width = widthParent;
          imageAttrs.height = parseInt(imageAttrs.height * rel);
        }
        image = new ContentEdit.Image(imageAttrs);
        parent.attach(image, index);
        image.focus();
        modal.hide();
        dialog.hide();
        return callback(true);
      };
    })(this));
    app.attach(modal);
    app.attach(dialog);
    modal.show();
    return dialog.show();
  };

  return ImagePicker;

})(ContentTools.Tool);

ContentTools.ImagePickerDialog = (function(superClass) {
  extend(ImagePickerDialog, superClass);

  function ImagePickerDialog(data, previewElement) {
    if (previewElement == null) {
      previewElement = null;
    }
    ImagePickerDialog.__super__.constructor.call(this, 'Selecciona una imagen para el post');
    this._state = 'populated';
    this._imageURL = data;
    this._previewElement = previewElement;
  }

  ImagePickerDialog.prototype.mount = function() {
    var domActions;
    ImagePickerDialog.__super__.mount.call(this);
    ContentEdit.addCSSClass(this._domElement, 'ct-imagepicker-dialog');
    ContentEdit.addCSSClass(this._domElement, 'ct-imagepicker-dialog--empty');
    ContentEdit.addCSSClass(this._domView, 'ct-imagepicker-dialog--empty');
    this._selectImage = document.createElement("select");
    this._selectImage.className += " masonry";
    this._domView.appendChild(this._selectImage);
    domActions = this.constructor.createDiv(['ct-control-group', 'ct-control-group--right']);
    this._domControls.appendChild(domActions);
    this._domCancel = this.constructor.createDiv(['ct-control', 'ct-control--text', 'ct-control--cancel']);
    this._domCancel.textContent = ContentEdit._('Cancelar');
    domActions.appendChild(this._domCancel);
    this._domOK = this.constructor.createDiv(['ct-control', 'ct-control--text', 'ct-control--upload']);
    this._domOK.textContent = ContentEdit._('Aceptar');
    domActions.appendChild(this._domOK);
    $.getJSON('getImages', (function(_this) {
      return function(data) {
        var d, i, len, option;
        for (i = 0, len = data.length; i < len; i++) {
          d = data[i];
          if (d.nodetype === "5040") {
            option = document.createElement("option");
            option.value = d.nodeid + ',' + d.file + ',' + d.width + ',' + d.height;
            option.setAttribute('data-img-src', d.file);
            _this._selectImage.appendChild(option);
          }
        }
        return $(_this._selectImage).imagepicker();
      };
    })(this));
    this._addDOMEventListeners();
    return this.trigger('CropImageDialog.mount');
  };

  ImagePickerDialog.prototype._addDOMEventListeners = function() {
    ImagePickerDialog.__super__._addDOMEventListeners.call(this);
    this._domCancel.addEventListener('click', (function(_this) {
      return function(ev) {
        return _this.trigger('cancel');
      };
    })(this));
    return this._domOK.addEventListener('click', (function(_this) {
      return function(ev) {
        var imageAttrs, imageSize, value, valueSplitted;
        value = _this._selectImage.value;
        valueSplitted = value.split(',');
        imageAttrs = {
          'data-xid': valueSplitted[0]
        };
        imageSize = [valueSplitted[2], valueSplitted[3]];
        return _this.trigger('ok', valueSplitted[1], imageSize, imageAttrs);
      };
    })(this));
  };

  return ImagePickerDialog;

})(ContentTools.DialogUI);
