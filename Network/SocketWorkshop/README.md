# Ethical Hacking Workshop : Socket

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

---

**Note:** An eavesdropper (Eve) who observes \(p\), \(g\), \(A\) and \(B\) but does not know \(a\) or \(b\) faces the discrete logarithm problem (recovering \(a\) from \(A=g^a\pmod p\) or \(b\) from \(B\)), which is believed to be computationally hard for appropriately chosen parameters.

### Challenge goal

The server will send you (A,p,g), so you will be able to compute K which is going to be the key to encrypt and decrypt the communication. But you will also have to send B that you can compute with (b,p,g). 

Then the server should send the flag and you just need to decrypt it with the key K.

**Note:** Because we are using the XOR algorithm you have to compute K mod 256, in a real context you should avoid to do that and use more complex algorithm such as AES.

*HOST = local-network-ip*
*PORT = 9003*