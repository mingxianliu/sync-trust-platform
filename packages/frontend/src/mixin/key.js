import streamSaver from 'streamsaver';
import { LocalStorage } from 'quasar';
streamSaver.mitm = './mitm.html'

var g_publicKey = null;
var g_privateKey = null;

export const $_handleFile = async (
  targetFile,
  type,
  key = null,
  decryptFileCallBack,
) => {
  // type 0: 上傳公鑰 1: 加密檔案 2: 匯入私鑰 3: 需解密檔案
  switch (type) {
    case 0:
      g_publicKey = await importPublicKey(key);
      break;
    case 1:
      return encryptFile(targetFile);
    case 2:
      return handleAddPrivateKey(targetFile);
    case 3:
      return decryptFile(targetFile, decryptFileCallBack);
    case 4:
      break;
  }
};

const handleAddPrivateKey = (key) => {
  return new Promise(async (resolve, reject) => {
    g_privateKey = await importPrivateKey(key)
      .then((res) => {
        resolve(res);
        return res;
      })
      .catch((err) => {
        return reject(err);
      });
  });
};
// 暫時沒用了
const encryptFile = (objFile) => {
  return new Promise(async (resolve, reject) => {
    let fileBytes = await readFile(objFile).catch((err) => {
      // console.error('encryptFile.err.1' + err);
      return reject(err);
    });

    fileBytes = new Uint8Array(fileBytes);
    const nToOutput = fileBytes.length;

    let nChunk = parseInt(nToOutput / 190);
    if (nToOutput % 190 > 0) {
      nChunk++;
      // console.log('(fileBytes.length % 190): ' + (fileBytes.length % 190));
      // console.log('nChunk: ' + nChunk);
    }

    let resultBytes = new Uint8Array(nChunk * 256);
    //

    for (let i = 0; i < nChunk; i++) {
      let r = i * 190 + 190;
      if (r >= nToOutput) r = nToOutput;
      const plaintextBytes = fileBytes.slice(i * 190, r);
      //
      let cipherBytes = await encryptRSA(g_publicKey, plaintextBytes).catch(
        function (err) {
          // console.error('encryptFile.err.2: ' + err);
          return reject(err);
        },
      );
      // console.log('plaintext encrypted');

      cipherBytes = new Uint8Array(cipherBytes);
      // console.log('cipherBytes: ' + cipherBytes.length);

      for (let j = 0; j < cipherBytes.length; j++)
        resultBytes[i * 256 + j] = cipherBytes[j];
    }
    const blob = new Blob([resultBytes], { type: 'application/download' });
    const blobUrl = URL.createObjectURL(blob);
    return resolve({ file: blob, url: blobUrl, name: objFile.name });
  });
};

export const $_encryptChunks = async (nChunk, chunks) => {
  return new Promise(async (resolve, reject) => {
    const fileBytes = new Uint8Array(chunks);
    const resultBytes = new Uint8Array(nChunk * 256);
    const nToOutput = fileBytes.length;

    for (let i = 0; i < nChunk; i++) {
      let r = i * 190 + 190;
      r = r >= nToOutput ? nToOutput : r;
      const plainTextBytes = fileBytes.slice(i * 190, r);

      let cipherBytes = await encryptRSA(g_publicKey, plainTextBytes).catch(
        (err) => {
          // console.error('encryptFile.err: ' + err);
          return reject(err);
        },
      );
      // console.log('plaintext encrypted');

      cipherBytes = new Uint8Array(cipherBytes);
      // console.log('cipherBytes: ' + cipherBytes.length);

      for (let j = 0; j < cipherBytes.length; j++)
        resultBytes[i * 256 + j] = cipherBytes[j];
    }
    return resolve(resultBytes);
  });
};

// 暫時沒用了 舊版
// const decryptFile = (objFile) => {
//   return new Promise(async (resolve, reject) => {
//     let fileBytes = await readFile(objFile).catch((err) => {
//       // console.error('decryptFile.err.1' + err);
//       return reject(err);
//     });

//     fileBytes = new Uint8Array(fileBytes);
//     // console.log('fileBytes: ' + fileBytes.length);

//     const nChunk = fileBytes.length / 256;
//     const resultBytes = new Uint8Array(nChunk * 190);

//     let nOutput = 0;

//     for (let i = 0; i < nChunk; i++) {
//       const cipherTextBytes = fileBytes.slice(i * 256, i * 256 + 256);
//       // console.log('cipherTextBytes: ' + cipherTextBytes.length);

//       let plaintextBytes = await decryptRSA(
//         g_privateKey,
//         cipherTextBytes,
//       ).catch(function (err) {
//         console.error('decryptFile.err.2: ' + err);
//         return reject(err);
//       });

//       plaintextBytes = new Uint8Array(plaintextBytes);
//       // console.log('cipher decrypted: ' + plaintextBytes.length);
//       for (let j = 0; j < plaintextBytes.length; j++) {
//         resultBytes[i * 190 + j] = plaintextBytes[j];
//         nOutput++;
//       }
//       // console.log('nOutput: ' + nOutput);
//     }
//     // console.log('cipher decrypted');
//     // console.log(resultBytes.length);

//     const blob = new Blob([resultBytes.slice(0, nOutput)], {
//       type: 'application/download',
//     });
//     const blobUrl = URL.createObjectURL(blob);
//     return resolve({ file: blob, url: blobUrl, name: objFile.name });
//   });
// };

const decryptFile = async (targetFile, decryptFileCallBack) => {
  LocalStorage.set('_isStop', 'false', { path: '/' });

  return new Promise(async (resolve, reject) => {
    const filename = targetFile.name.slice(0, -4);
    const fileStream = streamSaver.createWriteStream(filename, {
      size: 1024, // Makes the percentage visiable in the download
    });
    window.writer = fileStream.getWriter();
    //
    const fileSize = targetFile.size;
    console.log('size: ' + fileSize);

    let nOutput = 0;
    while (nOutput < fileSize) {
      decryptFileCallBack(nOutput, fileSize);
      if (LocalStorage.getItem('_isStop') === 'true') {
        LocalStorage.set('_isStop', 'false', { path: '/' });
        writer.close();
        return reject();
      }

      let n = fileSize - nOutput;
      if (n > 256) n = 256;
      const chunks = await targetFile
        .slice(nOutput, nOutput + 256)
        .arrayBuffer();

      let cipherTextBytes = new Uint8Array(chunks);
      // console.log('cipherTextBytes', cipherTextBytes.length);

      let plaintextBytes = await decryptRSA(
        g_privateKey,
        cipherTextBytes,
      ).catch(function (err) {
        return reject(err);
      });
      if (plaintextBytes === undefined) return;

      let view = new Uint8Array(plaintextBytes);

      writer.write(view);
      nOutput += n;
    }
    decryptFileCallBack(nOutput, fileSize);
    writer.close();
    return resolve(true);
  });
};

// 另一個版本沒用到
// const decryptFile2 = async (targetFile) => {
//   return new Promise(async (resolve, reject) => {
//     const fileStream = streamSaver.createWriteStream(`${targetFile.name}`, {
//       size: 1024, // Makes the percentage visiable in the download
//     });
//     window.writer = fileStream.getWriter();
//     //
//     const fileSize = targetFile.size;
//     console.log('size: ' + fileSize);

//     let nOutput = 0;
//     while (nOutput < fileSize) {
//       let n = fileSize - nOutput;
//       if (n > 256) n = 256;
//       const cipherTextBytes = await targetFile
//         .slice(nOutput, nOutput + 256)
//         .arrayBuffer();

//       let plaintextBytes = await decryptRSA(
//         g_privateKey,
//         cipherTextBytes,
//       ).catch(function (err) {
//         console.error('decryptFile.err.2: ' + err);
//         return reject(err);
//       });

//       let view = new Uint8Array(plaintextBytes);

//       writer.write(view);
//       nOutput += n;
//     }

//     writer.close();
//     return resolve(true);
//   });
// };

const readFile = (file) => {
  return new Promise((resolve) => {
    var fr = new FileReader();
    fr.onload = () => {
      resolve(fr.result);
    };
    fr.readAsArrayBuffer(file);
  });
};

//
//
//
//
// api 相關 cryptoutil

const encryptRSA = async (key, plaintext) => {
  let encrypted = await window.crypto.subtle.encrypt(
    {
      name: 'RSA-OAEP',
    },
    key,
    plaintext,
  );
  return encrypted;
};

const importPrivateKey = async (pkcs8Pem) => {
  return await window.crypto.subtle.importKey(
    'pkcs8',
    getPkcs8Der(pkcs8Pem),
    {
      name: 'RSA-OAEP',
      hash: 'SHA-256',
    },
    true,
    ['decrypt'],
  );
};

const getPkcs8Der = (pkcs8Pem) => {
  const pemHeader = '-----BEGIN PRIVATE KEY-----';
  const pemFooter = '-----END PRIVATE KEY-----';
  const pemContents = pkcs8Pem.substring(
    pemHeader.length,
    pkcs8Pem.length - pemFooter.length,
  );
  const binaryDerString = window.atob(pemContents);
  return str2ab(binaryDerString);
};

const importPublicKey = async (spkiPem) => {
  return await window.crypto.subtle.importKey(
    'spki',
    getSpkiDer(spkiPem), // key
    {
      name: 'RSA-OAEP',
      hash: 'SHA-256',
    },
    true,
    ['encrypt'],
  );
};

const getSpkiDer = (spkiPem) => {
  const pemHeader = '-----BEGIN PUBLIC KEY-----';
  const pemFooter = '-----END PUBLIC KEY-----';
  var pemContents = spkiPem.substring(
    pemHeader.length,
    spkiPem.length - pemFooter.length,
  );
  var binaryDerString = window.atob(pemContents);
  return str2ab(binaryDerString);
};

// https://stackoverflow.com/a/11058858
function str2ab(str) {
  const buf = new ArrayBuffer(str.length);
  const bufView = new Uint8Array(buf);
  for (let i = 0, strLen = str.length; i < strLen; i++) {
    bufView[i] = str.charCodeAt(i);
  }
  return buf;
}

const decryptRSA = async (key, cipherText) => {
  let decrypted = await window.crypto.subtle.decrypt(
    {
      name: 'RSA-OAEP',
    },
    key,
    cipherText,
  );
  return decrypted;
};

export const $_sha512 = async (string) => {
  const utf8 = new TextEncoder().encode(string);
  const hashBuffer = await crypto.subtle.digest('SHA-512', utf8);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray
    .map((bytes) => bytes.toString(16).padStart(2, '0'))
    .join('');
  return hashHex;
};
