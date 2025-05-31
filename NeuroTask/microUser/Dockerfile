FROM node:16

WORKDIR /home/node/app

COPY package*.json ./
RUN npm install


RUN mkdir -p ./src
COPY ./src ./src

EXPOSE 3009

CMD ["node", "./src/index.js"]