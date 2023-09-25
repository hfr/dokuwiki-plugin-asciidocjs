import * as ASCIIDOC from "./node_modules/@asciidoctor/core/dist/browser/asciidoctor.js";
const Asciidoctor=ASCIIDOC.default;
import * as KROKI from "./node_modules/asciidoctor-kroki/dist/browser/asciidoctor-kroki.js";
const AsciidoctorKroki=KROKI.default;
try {
  var args = process.argv.slice(1);
} catch (e) {
  var args = [];
}

if (args.length > 0) {
  if (args.length > 1) {
    var save_mode = args[2];
  } else {
    var save_mode = 'server';
  }
  const asciidoctor = Asciidoctor();
  const registry = asciidoctor.Extensions.create();
  AsciidoctorKroki.register(registry);
  async function doinput() {
    var data = "";
    for await (const chunk of process.stdin) {data += chunk;}
    return data;
  }
  var doc=await doinput();
  var html = asciidoctor.convert(doc, 
      {safe: save_mode, header_footer: false, extension_registry: registry});
  process.stdout.write(html);
}


