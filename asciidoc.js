import Asciidoctor from "./node_modules/@asciidoctor/core/dist/browser/asciidoctor.js";

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
  const asciidoctor = Asciidoctor()
  async function doinput() {
    var data = "";
    for await (const chunk of process.stdin) {data += chunk;}
    return data;
  }
  var doc=await doinput();
//  process.stdout.write(doc);
  var html = asciidoctor.convert(doc, {safe: save_mode, header_footer: false});
  process.stdout.write(html);
} else {
  jQuery( function() {
    var asciidoctor = Asciidoctor();
    for (let i = 0; i < asciidocs.length; i++) {
      var json = document.getElementById(asciidocs[i]["SID"]).textContent;
      var target = document.getElementById(asciidocs[i]["DID"]);
      var doc = JSON.parse(json);
      var html = asciidoctor.convert(doc.text, {safe: save_mode, header_footer: false});
      target.innerHTML = html;
    }
  });

}


