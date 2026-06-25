import React, { useState } from "react";
import {  Row, Col, Button } from "antd";

import SEP from "./SEP";
import Resume from "./Resume";
import SPRI from "./SPRI";
import Triase from "./Triase";


export default function Index({ nama_pasien, nomer_rm, kode_reg }) {
    return (
        <>
            <Row gutter={[8, 8]} style={{ width: 350 }}>
                <Col span={8}>
                    <SEP kode_reg={kode_reg} />
                    <Resume kode_reg={kode_reg} />
                    <SPRI kode_reg={kode_reg} />
                </Col>

                <Col span={8}>
                    <Triase kode_reg={kode_reg} />
                </Col>

                <Col span={8}>
                    <SPRI kode_reg={kode_reg} />
                </Col>

                <Col span={8}>
                    <Triase kode_reg={kode_reg} />
                </Col>
            </Row>
        </>
    );
}
