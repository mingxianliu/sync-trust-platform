from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa, padding
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives import serialization
import os, sys


with open(sys.argv[1], 'rb') as key_file:
    private_key = serialization.load_pem_private_key(
        key_file.read(),
        password=None,
        backend=default_backend()
    )

with open(sys.argv[2], 'rb') as f:
    encrypted_data = f.read()

decrypted_data = b''
for i in range(0, len(encrypted_data), 256):
    decrypted_data += private_key.decrypt(
        encrypted_data[i:i + 256],
        padding.OAEP(
            mgf=padding.MGF1(algorithm=hashes.SHA256()),
            algorithm=hashes.SHA256(),
            label=None
        )
    )

with open(sys.argv[3], 'wb') as f:
    f.write(decrypted_data)