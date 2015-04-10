
exports.name = 'local';
exports.desc = 'init local debug env';

exports.register = function(commander) {

    commander
        .option('-r, --root <path>', 'set deploy root path')
        .action(function(){
            var argv = Array.prototype.slice.call(arguments);
            var options = argv.pop();
            var cmd = argv.shift();

            function _getServerRoot(){
                var rcFile = fis.project.getTempPath('server/conf.json');
                if(fis.util.isFile(rcFile)){
                      var conf = fis.util.readJSON(rcFile);
                      if(fis.util.isDir(conf.root)){
                          return conf.root;
                      }else{
                          fis.log.error(conf.root + " is not a dir.");
                      }
                }else{
                    fis.log.error(rcFile + " is not a file.");
                }
            };

            switch(cmd){
                case "init":
                    var serverRoot = _getServerRoot();
                    var libDir = __dirname + "/server-lib/";
                    fis.util.copy(libDir, serverRoot);
                    fis.log.notice("Init Finish");
                    break;
                default:
                    commander.help();
            }

        });



}
