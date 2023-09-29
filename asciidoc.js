import * as ASCIIDOC from "./node_modules/@asciidoctor/core/dist/browser/asciidoctor.js";
import * as KROKI from "./node_modules/asciidoctor-kroki/dist/browser/asciidoctor-kroki.js";
const Asciidoctor=ASCIIDOC.default;
try {
  var args = process.argv.slice(1);
} catch (e) {
  var args = [];
}

if (args.length > 0) {
  var extensions = JSON.parse(args[1]);
} else {
  var extensions = {};
}
if (args.length > 1) {
    var params = JSON.parse(args[2]);
} else {
    var params = {};
}
  const asciidoctor = Asciidoctor();
  const registry = asciidoctor.Extensions.create();
  if (extensions["kroki"]){
    const AsciidoctorKroki=KROKI.default;
    AsciidoctorKroki.register(registry);
  }
  registry.inlineMacro("Wikilink", function () {
    var self = this
    self.positionalAttributes("text");
    self.parseContentAs("raw");
    self.process(function (parent, target, attrs) {
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
  async function doinput() {
    var data = "";
    for await (const chunk of process.stdin) {data += chunk;}
    return data;
  }
  var doc=await doinput();
  var html = asciidoctor.convert(doc,params);
  process.stdout.write(html);



