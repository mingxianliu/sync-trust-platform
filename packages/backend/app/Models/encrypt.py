from cryptography.hazmat.primitives.asymmetric import padding
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.backends import default_backend  
import os, sys

public_key = serialization.load_pem_public_key(
  open(sys.argv[1], 'rb').read(),
  backend=default_backend()
)

with open(sys.argv[2], 'rb') as f:
  plaintext = f.read()

encrypted_data = b''  
for i in range(0, len(plaintext), 190):
  encrypted_data += public_key.encrypt(
    plaintext[i:i+190],
    padding.OAEP(
      mgf=padding.MGF1(algorithm=hashes.SHA256()),
      algorithm=hashes.SHA256(),
      label=None
    )
  )

with open(sys.argv[3], 'wb') as f:
  f.write(encrypted_data)