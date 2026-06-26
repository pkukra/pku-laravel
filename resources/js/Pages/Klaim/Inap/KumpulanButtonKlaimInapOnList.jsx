import React, { useState } from "react";
import { Row, Col, Button } from "antd";

import SEP from "./SEP";
import Resume from "./Resume";
import SPRI from "./SPRI";
import Triase from "./Triase";
import LabRadiologi from "./LabRadiologi";
import Kwitansi from "./Kwitansi";
import LaporanOperasi from "./LaporanOperasi";
import Anastesi from "./Anastesi";

export default function Index({ nama_pasien, nomer_rm, kode_reg }) {
    return (
        <>
            <Row gutter={[8, 8]} style={{ width: 350 }}>
                <Col span={8}>
                    <SEP kode_reg={kode_reg} />
                    <Resume kode_reg={kode_reg} />
                    <SPRI kode_reg={kode_reg} />
                    <Triase kode_reg={kode_reg} />
                </Col>
                <Col span={8}>
                    <LabRadiologi kode_reg={kode_reg} nomer_rm={nomer_rm} />
                    <Kwitansi kode_reg={kode_reg} />
                </Col>
                <Col span={8}>
                    <LaporanOperasi kode_reg={kode_reg} />
                    <Anastesi kode_reg={kode_reg} />
                </Col>
            </Row>
        </>
    );
}
