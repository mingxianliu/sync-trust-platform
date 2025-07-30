import JSZip from 'jszip';

export const toZip = async (file, outputName) => {
  const zip = new JSZip();
  if (Array.isArray(file)) {
    file.forEach((item) => {
      zip.file(item.name, item);
    });
  } else {
    zip.file(file.name, file);
  }

  const content = await zip.generateAsync({
    type: 'blob',
  });
  return new File([content], outputName, { type: 'application/zip' });
};
