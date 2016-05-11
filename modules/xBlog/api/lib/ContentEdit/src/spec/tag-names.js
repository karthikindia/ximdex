// Generated by CoffeeScript 1.10.0
describe('`ContentEdit.TagNames.get()`', function() {
  return it('should return a singleton instance of TagNames`', function() {
    var tagNames;
    tagNames = new ContentEdit.TagNames.get();
    return expect(tagNames).toBe(ContentEdit.TagNames.get());
  });
});

describe('`ContentEdit.TagNames.register()`', function() {
  return it('should register a class with one or more tag names', function() {
    var tagNames;
    tagNames = new ContentEdit.TagNames.get();
    tagNames.register(ContentEdit.Node, 'foo');
    tagNames.register(ContentEdit.Element, 'bar', 'zee');
    expect(tagNames.match('foo')).toBe(ContentEdit.Node);
    expect(tagNames.match('bar')).toBe(ContentEdit.Element);
    return expect(tagNames.match('zee')).toBe(ContentEdit.Element);
  });
});

describe('`ContentEdit.TagNames.match()`', function() {
  var tagNames;
  tagNames = new ContentEdit.TagNames.get();
  it('should return a class registered for the specifed tag name', function() {
    return expect(tagNames.match('img')).toBe(ContentEdit.Image);
  });
  return it('should return `ContentEdit.Static` if no match is found for the tag name', function() {
    return expect(tagNames.match('bom')).toBe(ContentEdit.Static);
  });
});
