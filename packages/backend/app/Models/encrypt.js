const crypto = require('crypto');
const fs = require('fs');
const {
  Transform
} = require('stream');

const input = fs.createReadStream(process.argv[3]);
const output = fs.createWriteStream(process.argv[4] + '.tmp');

class EncryptedStream extends Transform {
  constructor() {
    super();
    this.buffered = Buffer.alloc(0);
    this.publicKey = crypto.createPublicKey({
      key: fs.readFileSync(process.argv[2]).toString('utf-8'),
      format: 'pem'
    });
  }

  _transform(chunk, encoding, callback) {
    this.buffered = Buffer.concat([this.buffered, chunk]);

    while (this.buffered.length >= 190) {
      const decrypedChunk = crypto.publicEncrypt({
        key: this.publicKey,
        padding: crypto.constants.RSA_PKCS1_OAEP_PADDING, 
        oaepHash: 'sha256'
      }, this.buffered.slice(0, 190));

      this.buffered = this.buffered.slice(190);
      this.push(decrypedChunk);
    }

    callback();
  }

  _flush(callback) {
    if (this.buffered.length > 0) {
      const encrypted = crypto.publicEncrypt({
        key: this.publicKey,
        padding: crypto.constants.RSA_PKCS1_OAEP_PADDING, 
        oaepHash: 'sha256'
      }, this.buffered);

      this.push(encrypted);
    }
    callback();
  }
}

input.pipe(new EncryptedStream()).pipe(output);

output.on('finish', () => {
  fs.rename(process.argv[4] + '.tmp', process.argv[4], function (err) {

  });
});