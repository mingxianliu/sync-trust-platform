const crypto = require('crypto');
const fs = require('fs');
const {
  Transform
} = require('stream');

const input = fs.createReadStream(process.argv[3]);
const output = fs.createWriteStream(process.argv[4] + '.tmp');

class DecryptStream extends Transform {
  constructor() {
    super();
    this.buffered = Buffer.alloc(0);
    this.privateKey = crypto.createPrivateKey({
      key: fs.readFileSync(process.argv[2]).toString('utf-8'),
      format: 'pem',
      type: 'pkcs8'
    });
  }

  _transform(chunk, encoding, callback) {
    this.buffered = Buffer.concat([this.buffered, chunk]);

    while (this.buffered.length >= 256) {
      const decrypedChunk = crypto.privateDecrypt({
        key: this.privateKey,
        padding: crypto.constants.RSA_PKCS1_OAEP_PADDING,
        oaepHash: 'sha256',
      }, this.buffered.slice(0, 256));

      this.buffered = this.buffered.slice(256);
      this.push(decrypedChunk);
    }

    callback();
  }

  _flush(callback) {
    if (this.buffered.length > 0) {
      const decryptedChunk = crypto.privateDecrypt({
        key: this.privateKey,
        padding: crypto.constants.RSA_PKCS1_OAEP_PADDING,
        oaepHash: 'sha256',
      }, this.buffered);

      this.push(decryptedChunk);
    }
    callback();
  }
}

input.pipe(new DecryptStream()).pipe(output);

output.on('finish', () => {
  fs.rename(process.argv[4] + '.tmp', process.argv[4], function (err) {

  });
});