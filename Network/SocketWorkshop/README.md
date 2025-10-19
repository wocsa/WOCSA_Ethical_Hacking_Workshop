# Ethical Hacking Workshop : Socket

## Warning
This workshop is for educational purposes only. Ethical hacking is conducted with the explicit permission of the system owner to improve security.

## Table of Contents

- [Ethical Hacking Workshop : Socket](#ethical-hacking-workshop--socket)
  - [Warning](#warning)
  - [Table of Contents](#table-of-contents)
  - [Main goal of the workshop](#main-goal-of-the-workshop)
  - [But first, what's a socket ?](#but-first-whats-a-socket-)
  - [Flag platform](#flag-platform)
  - [First challenge : time to hands on](#first-challenge--time-to-hands-on)
  - [Second challenge : send your first packet](#second-challenge--send-your-first-packet)
  - [Third challenge : what about the packets](#third-challenge--what-about-the-packets)
  - [Fourth challenge : time to secure your communication](#fourth-challenge--time-to-secure-your-communication)
    - [Description of the original protocol](#description-of-the-original-protocol)
    - [Challenge goal](#challenge-goal)
  - [Am I safe now ?](#am-i-safe-now-)
    - [MITM attack on Diffie‑Hellman (DH) — explanation + diagrams](#mitm-attack-on-diffiehellman-dh--explanation--diagrams)
      - [Short summary](#short-summary)
      - [Message flow (step by step)](#message-flow-step-by-step)
    - [Digital Signatures \& ECDSA](#digital-signatures--ecdsa)
      - [What is a Digital Signature?](#what-is-a-digital-signature)
      - [How Digital Signatures Work](#how-digital-signatures-work)
      - [Signature Process:](#signature-process)
      - [ECDSA vs RSA](#ecdsa-vs-rsa)
  - [Last challenge : little practice with signature](#last-challenge--little-practice-with-signature)
  - [Conclusion](#conclusion)

## Main goal of the workshop

You're going to develop your first network socket. The idea is to learn how to create a socket, read a message and send a message through a socket. You will have some challenges to solve which will make you practice network socket and also other subjects such as cryptography.

## But first, what's a socket ?

A *network socket* is a software structure that serves as an endpoint for sending and receiving data across the network.

A *network socket* is identified by its *transport protocol*, *IP address* and *port number*.

Thus, when you want to create a socket as a client, you must specify the server's identifier.

For this workshop, we are going to use mainly *python* which is more user-friendly for socket, but you can of course use *C* to practice low-level programming.

## Flag platform

For an interactive experience during the workshop, your task is to locate all the flags in the challenges and validate them via the web platform at:

http://local-network-ip:8080

To start the platform and challenges, the facilitator must run the launch_workshop.sh script.

## First challenge : time to hands on

It's time to create your first socket and receive your first message !

Here is some function you'll need to do that : 

To get started, import Python’s built-in socket library with:

```python
import socket
```

To complete the challenge, you’ll need to use the following socket functions:

- socket.socket

- socket.connect

- socket.recv

*HOST = local-network-ip*
*PORT = 9001*

You can find the official Python documentation for these functions here:
https://docs.python.org/3/library/socket.html

To read the message you will also need the .decode() function.

You have received the flag! However, the flag appears to be unreadable. Fortunately, the server also provided the key in hexadecimal format and the encryption type.

## Second challenge : send your first packet

Now that you know how to create a socket and receive a message, it's time to send an answer. You can reuse your program to receive a message. This time you are going to receive two integers and one arithmetic operation. 

The goal is to send the result of A op B in less than 2 seconds. 

To complete the challenge, you'll need a new function : socket.sendall

*HOST = local-network-ip*
*PORT = 9002*

To read a message, you have needed to decode it during the first challenge, so now, when you want to send something, you have to encode it with .encode().

If you manage to send the result in less than two seconds, the server will send you the flag;

## Third challenge : what about the packets

You are now able to create a socket, establish a connection and interact with the server. But have you thought about your packet that are going throught the network ? I hope so, and in this case, you should have found the third flag.

If it's not the case, run Wireshark and look at the packets.

Is it ok for you that everything is in plaintext ? Just think about someone in the middle of your communication with the server, intercepting all the traffic and above all your password.

(HINT : port 9999 and UDP broadcast)

## Fourth challenge : time to secure your communication

As we have seen in the last part, your communication through the socket is in plaintext. So, we want to encrypt it with a realistic method. For this we will use Diffie-Hellman key exchange.

If you want to learn more about Diffie-Hellman, here is a link : https://www.crypto101.io

The Diffie-Hellman key exchange is a method for the client and the server to exchange a public key through the network and then communicate safely thanks of a symmetric cryptography algorithm (We will use XOR here to avoid importing library that are not available in default python).

But now, let's talk about what you are going to compute :

### Description of the original protocol

1. Alice and Bob agree on a prime number `p` and a generator `g` of the multiplicative group `(Z/pZ)*` (with `g < p`).  
   *(They may choose `p` and `g` in advance or exchange them in the clear at the start of the session — doing so does not improve Eve’s chances.)*
2. Alice chooses a secret number `a` and sends `A = g^a mod p`.
3. Bob chooses a secret number `b` and sends `B = g^b mod p`.
4. Alice computes `K = B^a mod p = g^(ba) mod p`.
5. Bob computes `K = A^b mod p = g^(ab) mod p`.
6. Both share the same secret key: `K = g^(ab) mod p`.

Because exponentiation in this group is commutative with respect to the exponents, both Alice and Bob obtain the same value \(g^{ab} \bmod p\), which can be used as a shared secret key.

**Note:** An eavesdropper (Eve) who observes \(p\), \(g\), \(A\) and \(B\) but does not know \(a\) or \(b\) faces the discrete logarithm problem (recovering \(a\) from \(A=g^a\pmod p\) or \(b\) from \(B\)), which is believed to be computationally hard for appropriately chosen parameters.

### Challenge goal

The server will send you (A,p,g), so you will be able to compute K which is going to be the key to encrypt and decrypt the communication. But you will also have to send B that you can compute with (b,p,g). 

Then the server should send the flag and you just need to decrypt it with the key K.

**Note:** Because we are using the XOR algorithm you have to compute K mod 256, in a real context you should avoid to do that and use more complex algorithm such as AES.

*HOST = local-network-ip*
*PORT = 9003*

## Am I safe now ?

Check Wireshark — did you discover any other flags? Hopefully not. As mentioned earlier, recovering a or b is computationally infeasible for an attacker. Nevertheless, a private key can be leaked by mistake. To mitigate this risk, use **Ephemeral Diffie‑Hellman (DHE)**: the server generates a fresh ephemeral private key for each connection (or session). This provides perfect forward secrecy — even if an attacker compromises an old key, they cannot decrypt subsequent sessions.

We're not going to implement DHE here, but it's interesting to know about. Now I ask you to take the role of an attacker and try to find a way to read the communication.

### MITM attack on Diffie‑Hellman (DH) — explanation + diagrams

#### Short summary
In a classic **unauthenticated** Diffie‑Hellman exchange, an active adversary (Eve) can position herself between Alice and Bob, intercepting and replacing the public values. Instead of a single Alice↔Bob exchange, Eve performs **two** DH exchanges (Alice↔Eve and Eve↔Bob). Alice and Bob believe they are communicating securely with each other, but in reality they each share distinct keys with Eve, who can read, modify and forward messages — this is a **Man‑In‑The‑Middle (MITM)** attack.

#### Message flow (step by step)
1. Alice picks a secret `a` and computes `A = g^a mod p`.  
2. Alice sends `A` → **Eve** (Eve intercepts it).  
3. Eve picks her own secret `e1`, computes `E1 = g^e1 mod p`, and **sends `E1` to Bob**, pretending it is `A`.  
4. Bob picks a secret `b`, computes `B = g^b mod p`, and **sends `B` → Eve** (intercepted).  
5. Eve picks another secret `e2`, computes `E2 = g^e2 mod p`, and **sends `E2` to Alice**, pretending it is `B`.  
6. Alice computes `K_A = E2^a mod p = g^{a*e2}` (believing she shares this key with Bob).  
   Bob computes `K_B = E1^b mod p = g^{b*e1}` (believing he shares this key with Alice).  
   Eve, knowing `e1` and `e2`, computes both `K_EA = A^{e1} = g^{a*e1}` and `K_EB = B^{e2} = g^{b*e2}`.  
7. Now Eve can:
   - Decrypt messages from Alice using `K_EA`, read or modify them, then re‑encrypt using `K_EB` and forward to Bob.
   - Do the same for messages from Bob to Alice.

**Result:** Alice and Bob think they share a single secret key, but actually there are two keys (Alice↔Eve and Eve↔Bob). Eve fully mediates and controls the conversation.

```mermaid
sequenceDiagram
  participant Alice
  participant Eve as Eve (MITM)
  participant Bob

  Alice->>Eve: A = g^a mod p  (public)
  Eve-->>Bob: E1 = g^e1 mod p  (forged as A)
  Bob->>Eve: B = g^b mod p
  Eve-->>Alice: E2 = g^e2 mod p (forged as B)

  Note over Alice,Eve: Alice computes K_A = E2^a = g^(a*e2)
  Note over Bob,Eve: Bob computes K_B = E1^b = g^(b*e1)

  Alice->>Eve: Enc(K_A, "msg1")
  Eve->>Bob:   Dec(K_A) -> read/modify -> Enc(K_B, "msg1'")

  Bob->>Eve:   Enc(K_B, "reply")
  Eve->>Alice: Dec(K_B) -> read/modify -> Enc(K_A, "reply'")
```

### Digital Signatures & ECDSA

#### What is a Digital Signature?

A **digital signature** is a cryptographic method used to:

- Prove who sent the message (**authenticity**),
- Ensure that the message was not modified (**integrity**),
- Prevent the sender from denying it later (**non-repudiation**).

It is a core security mechanism in protocols like HTTPS, SSH, Git, and blockchain systems.

---

#### How Digital Signatures Work

Digital signatures are based on **asymmetric cryptography**: a pair of keys is used:

- **Private key** → used to **sign** the message.
- **Public key** → used to **verify** the signature.

#### Signature Process:

1. The sender (Alice) hashes the message.
2. She signs the hash using her **private key**.
3. The receiver (Bob) verifies the signature using Alice's **public key**.

---

#### ECDSA vs RSA

| Feature              | **ECDSA** (Elliptic Curve)              | **RSA**                            |
|----------------------|------------------------------------------|------------------------------------|
| Key size (for 128-bit security) | 256 bits                           | 3072 bits                         |
| Signature size       | ~64 bytes                                | ~256 bytes                        |
| Performance          | Fast (esp. on small devices)             | Slower for signing, fast for verify |
| Security basis       | Elliptic Curve Discrete Log Problem (ECDLP) | Integer Factorization           |
| Used in              | Bitcoin, TLS 1.3, SSH, JWT               | TLS 1.2, PGP, SSH, JWT            |
| Libraries            | Modern (cryptography, OpenSSL)           | Mature, widely available          |

**ECDSA** is ideal for modern systems where efficiency matters (IoT, mobile, blockchain).  
**RSA** is a good choice for compatibility with older systems.

```mermaid
sequenceDiagram
  participant Alice as Alice (Sender)
  participant Bob as Bob (Verifier)

  Alice->>Alice: hash = SHA256(message)
  Alice->>Alice: signature = Sign(hash, private_key)
  Alice->>Bob: Send message + signature

  Bob->>Bob: hash' = SHA256(message)
  Bob->>Bob: Verify(signature, hash', public_key)
  Bob-->>Bob: Valid? yes or not
```

## Last challenge : little practice with signature

**Goal:** demonstrate signing and verification in a networked setting and teach why signatures are necessary to prevent active attacks (MITM).  
Participants connect to a server that sends a plaintext flag together with an ECDSA signature and the server public key. The task is to:

1. Connect to the server (TCP).
2. Receive the server public key (PEM), the payload (flag), and the signature (all framed).
3. Verify the signature using the received public key.
4. If the signature is valid, display the flag.

This challenge uses Python 3.8+ (works on 3.7 but modern versions recommended). The following Python package is required:

- `cryptography` — for ECDSA key handling and verification.

Install it with pip:

```bash
python3 -m pip install --user cryptography
```

## Conclusion

Well, you have achieved a lot of work, now you know the basics of network socket and you also learned about cryptographic system. Next round will be about buffer !

**Note:**
A signed message is not encrypted — it can still be read by anyone, but any tampering will be detected.

A secure communication system needs both:

- Confidentiality (via encryption) to protect message content, and

- Authenticity (via signatures or MACs) to ensure trust and integrity.

```mermaid
graph TD

subgraph Encryption (Confidentiality)
  AliceEncrypts[Alice encrypts message with Bob's key]
  CipherText[Encrypted message]
  BobDecrypts[Bob decrypts with private key]
  EveEavesdropper[Eve sees ciphertext, but can't read it]

  AliceEncrypts --> CipherText --> BobDecrypts
  CipherText -.-> EveEavesdropper
end

subgraph Signature (Authenticity)
  AliceSigns[Alice signs message with her private key]
  PlainMessage[Plaintext message + signature]
  BobVerifies[Bob verifies with Alice's public key]
  EveTamper[Eve tries to modify message ➜ invalid signature]

  AliceSigns --> PlainMessage --> BobVerifies
  PlainMessage -.-> EveTamper
end
```