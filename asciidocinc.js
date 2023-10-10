jQuery( function() {
  var asciidoctor = Asciidoctor();
  const registry = asciidoctor.Extensions.create();

  if (extensions.kroki) {AsciidoctorKroki.register(registry);}

  registry.inlineMacro("Wikilink", function () {
    var self = this
    self.positionalAttributes("text");
    self.parseContentAs("raw");
    self.process(function (parent, target, attrs) {console.log(attrs);
      var text=attrs.text?attrs.text:target;
      target="/wiki/doku.php?id="+target;
      return self.createInline(parent, "anchor", text, {type: "link", target: target})
    })
  })

  registry.inlineMacro("Wikimedia", function () {
    var self = this
    self.positionalAttributes("text");
    self.parseContentAs("raw");
    self.process(function (parent, target, attrs) {console.log(attrs);
      var text=attrs.text?attrs.text:target;
      target="/wiki/lib/exe/fetch.php?media="+target;
      return self.createInline(parent, "anchor", text, {type: "link", target: target})
    })
  })

  params.extension_registry=registry;

  for (let i = 0; i < asciidocs.length; i++) {
    var json = document.getElementById(asciidocs[i]["SID"]).textContent;
    var target = document.getElementById(asciidocs[i]["DID"]);
    var doc = JSON.parse(json);
    var html = asciidoctor.convert(doc.text,params);
    target.innerHTML = html;
  }
});
